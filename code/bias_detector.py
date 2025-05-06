import pandas as pd
import numpy as np
from typing import List, Dict, Tuple, Optional
import logging
from data_preprocessor import DataPreprocessor
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, confusion_matrix

class BiasDetector:
    def __init__(self, preprocessor, sensitive_attributes, privileged_group, unprivileged_group):
        self.preprocessor = preprocessor
        self.sensitive_attributes = sensitive_attributes  # Now taken from config.json
        self.privileged_group = privileged_group
        self.unprivileged_group = unprivileged_group
        self.binary_threshold = 2

    def detect_available_attributes(self, data: pd.DataFrame) -> List[str]:
        """Detect which sensitive attributes are present in the dataset."""
        available_attrs = [attr for attr in self.sensitive_attributes if attr in data.columns]
        return available_attrs

    def is_binary(self, series: pd.Series) -> bool:
        """Check if a column is binary."""
        return len(series.unique()) <= self.binary_threshold

    def get_attribute_for_analysis(self, data):
        """Fetch sensitive attribute from config instead of asking the user."""
        available_attrs = [attr for attr in self.sensitive_attributes if attr in data.columns]

        if not available_attrs:
            print(f"Error: The specified sensitive attribute {self.sensitive_attributes} is not found in the dataset.")
            return None, None  # Exit if attribute not found

        selected_attr = available_attrs[0]  # Use the first matched attribute
        print(f"🔍 Using '{selected_attr}' as the sensitive attribute (from config)")

        return selected_attr, data[selected_attr]



    def process_categorical_attribute(self, series, column_name):
        """Automatically assign privileged/unprivileged groups based on config file."""
        
        if column_name in self.preprocessor.label_encoders:
            label_encoder = self.preprocessor.label_encoders[column_name]

            # Convert config values to encoded labels
            privileged_encoded = [label_encoder.transform([val])[0] for val in self.privileged_group if val in label_encoder.classes_]
            unprivileged_encoded = [label_encoder.transform([val])[0] for val in self.unprivileged_group if val in label_encoder.classes_]

            if not privileged_encoded or not unprivileged_encoded:
                print(f"⚠️ Warning: Config values for {column_name} do not match dataset encoding.")

            return series.map(lambda x: 1 if x in privileged_encoded else 0)
        
        return series  # If no encoding was needed, return as is


    def calculate_metrics(self, 
                     sensitive_attr: pd.Series, 
                     target: pd.Series) -> Dict[str, float]:
        """
        Calculate bias metrics for the dataset with improved error handling and logging.
        
        Args:
            sensitive_attr: Binary sensitive attribute series (1 for privileged, 0 for unprivileged)
            target: Binary target series
            
        Returns:
            Dictionary containing bias metrics
        """
        # Print group sizes for debugging
        privileged_count = (sensitive_attr == 1).sum()
        unprivileged_count = (sensitive_attr == 0).sum()
        print("\nGroup sizes:")
        print(f"Privileged group (1): {privileged_count}")
        print(f"Unprivileged group (0): {unprivileged_count}")
        
        # Calculate positive outcomes for each group
        privileged_positive = target[sensitive_attr == 1].sum()
        unprivileged_positive = target[sensitive_attr == 0].sum()
        
        print("\nPositive outcomes:")
        print(f"Privileged group positive outcomes: {privileged_positive}")
        print(f"Unprivileged group positive outcomes: {unprivileged_positive}")
        
        # Calculate probabilities with safety checks
        prob_positive_privileged = (privileged_positive / privileged_count 
                                if privileged_count > 0 else 0)
        prob_positive_unprivileged = (unprivileged_positive / unprivileged_count 
                                    if unprivileged_count > 0 else 0)
        
        print("\nProbabilities:")
        print(f"P(Y=1|privileged): {prob_positive_privileged:.3f}")
        print(f"P(Y=1|unprivileged): {prob_positive_unprivileged:.3f}")
        
        # Calculate metrics with safety checks
        statistical_parity_diff = prob_positive_privileged - prob_positive_unprivileged
        
        # Handle division by zero in disparate impact
        if prob_positive_privileged > 0:
            disparate_impact = prob_positive_unprivileged / prob_positive_privileged
        elif prob_positive_unprivileged == 0:  # Both are zero
            disparate_impact = 1.0  # Equal treatment (although both are zero)
        else:  # priv = 0, unpriv > 0
            disparate_impact = float('inf')
        
        metrics = {
            'statistical_parity_difference': statistical_parity_diff,
            'disparate_impact': disparate_impact,
            'positive_rate_privileged': prob_positive_privileged,
            'positive_rate_unprivileged': prob_positive_unprivileged
        }
        
        print("\nCalculated metrics:")
        for metric, value in metrics.items():
            print(f"{metric}: {value:.3f}")
        
        return metrics

    def interpret_results(self, metrics: Dict[str, float]) -> str:
        """
        Interpret the bias metrics and provide a human-readable explanation with improved context.
        """
        spd = metrics['statistical_parity_difference']
        di = metrics['disparate_impact']
        priv_rate = metrics['positive_rate_privileged']
        unpriv_rate = metrics['positive_rate_unprivileged']
        
        interpretation = [
            f"\nDetailed Metrics Analysis:",
            f"- Positive outcome rate for privileged group: {priv_rate:.1%}",
            f"- Positive outcome rate for unprivileged group: {unpriv_rate:.1%}"
        ]
        
        # Statistical Parity Difference interpretation
        if pd.isna(spd):
            interpretation.append("- Statistical Parity Difference could not be calculated (check group sizes)")
        elif abs(spd) < 0.05:
            interpretation.append(f"- Statistical Parity Difference is {spd:.3f}, suggesting minimal bias")
        else:
            direction = "privileged" if spd > 0 else "unprivileged"
            interpretation.append(
                f"- Statistical Parity Difference is {spd:.3f}, indicating bias favoring the {direction} group"
            )
        
        # Disparate Impact interpretation
        if pd.isna(di):
            interpretation.append("- Disparate Impact could not be calculated (check group sizes)")
        elif di == float('inf'):
            interpretation.append("- Disparate Impact is undefined (privileged group has no positive outcomes)")
        elif 0.8 <= di <= 1.25:
            interpretation.append(f"- Disparate Impact is {di:.3f}, within acceptable range (0.8-1.25)")
        else:
            if di < 0.8:
                interpretation.append(
                    f"- Disparate Impact is {di:.3f}, indicating significant bias against unprivileged group"
                )
            else:
                interpretation.append(
                    f"- Disparate Impact is {di:.3f}, indicating significant bias against privileged group"
                )
        
        return "\n".join(interpretation)
        
    def detect_bias(self, X: pd.DataFrame, y: pd.Series) -> Tuple[str, Dict[str, float]]:
        """Detect bias in the entire dataset and return the sensitive attribute name."""

        # Step 1: Select sensitive attribute
        sensitive_attr_name, sensitive_attr = self.get_attribute_for_analysis(X)

        # If only one unique value, stop bias detection
        if sensitive_attr is None:
            print("\nSkipping bias detection due to lack of variation in the sensitive attribute.")
            return None, {}

        print(f"Using '{sensitive_attr_name}' as the sensitive attribute")

        # Step 2: Always process the sensitive attribute, even if it was auto-selected
        sensitive_attr = self.process_categorical_attribute(sensitive_attr, sensitive_attr_name)

        # If processing failed, skip further analysis
        if sensitive_attr is None:
            print("\nSkipping bias detection as no valid privileged group was selected.")
            return None, {}

        # Step 3: Compute Bias Metrics on the ENTIRE dataset
        bias_metrics = self.calculate_metrics(sensitive_attr, y)

        # Step 4: Show Bias Analysis for the whole dataset
        print("\nBias Detection Results (Entire Dataset):")
        print(self.interpret_results(bias_metrics))

        return sensitive_attr_name, bias_metrics  # Return attribute name too


    def compute_fairness_metrics(self, X_test, y_test, y_pred, sensitive_attr_name):
        """
        Compute Equalized Odds, Equalized Opportunity, and Predictive Parity.
        """
        sensitive_attr = X_test[sensitive_attr_name]
        
        # Get privileged & unprivileged groups
        privileged_group = (sensitive_attr == 1)
        unprivileged_group = (sensitive_attr == 0)

        # Confusion matrix components
        def get_confusion_values(y_true, y_pred, group):
            """
            Get confusion matrix values while handling both binary and multiclass classification.
            """
            y_true_group = y_true.loc[group] if isinstance(y_true, pd.Series) else y_true[group]
            y_pred_group = y_pred.loc[group] if isinstance(y_pred, pd.Series) else y_pred[group]

            # If group has no records or only one unique class, return zeroes to prevent errors
            if len(y_true_group) == 0 or len(y_pred_group) == 0 or len(np.unique(y_true_group)) < 2:
                return 0, 0, 0, 0

            cm = confusion_matrix(y_true_group, y_pred_group)

            # If binary classification, return TN, FP, FN, TP
            if cm.shape == (2, 2):
                return cm.ravel()

            # If multiclass classification, return overall accuracy-based confusion values
            tp = np.diag(cm)  # True Positives (diagonal values)
            fp = cm.sum(axis=0) - tp  # False Positives (column sum minus TP)
            fn = cm.sum(axis=1) - tp  # False Negatives (row sum minus TP)
            tn = cm.sum() - (tp + fp + fn)  # True Negatives (everything else)

            return tn.sum(), fp.sum(), fn.sum(), tp.sum()  # Summing over all classes

        if privileged_group.sum() == 0 or unprivileged_group.sum() == 0:
            print("\nWarning: One of the groups has no records in the test set. Fairness metrics may be unreliable.")

        tn_priv, fp_priv, fn_priv, tp_priv = get_confusion_values(y_test, y_pred, privileged_group)
        tn_unpriv, fp_unpriv, fn_unpriv, tp_unpriv = get_confusion_values(y_test, y_pred, unprivileged_group)

        # Compute metrics
        tpr_priv = tp_priv / (tp_priv + fn_priv) if (tp_priv + fn_priv) > 0 else 0  # True Positive Rate
        fpr_priv = fp_priv / (fp_priv + tn_priv) if (fp_priv + tn_priv) > 0 else 0  # False Positive Rate
        precision_priv = tp_priv / (tp_priv + fp_priv) if (tp_priv + fp_priv) > 0 else 0  # Predictive Parity

        tpr_unpriv = tp_unpriv / (tp_unpriv + fn_unpriv) if (tp_unpriv + fn_unpriv) > 0 else 0
        fpr_unpriv = fp_unpriv / (fp_unpriv + tn_unpriv) if (fp_unpriv + tn_unpriv) > 0 else 0
        precision_unpriv = tp_unpriv / (tp_unpriv + fp_unpriv) if (tp_unpriv + fp_unpriv) > 0 else 0

        # Equalized Odds (TPR & FPR should be similar across groups)
        equalized_odds = abs(tpr_priv - tpr_unpriv) + abs(fpr_priv - fpr_unpriv)

        # Equalized Opportunity (TPR should be similar across groups)
        equalized_opportunity = abs(tpr_priv - tpr_unpriv)

        # Predictive Parity (Precision should be similar across groups)
        predictive_parity = abs(precision_priv - precision_unpriv)

        # Print metrics
        print("\nFairness Metrics:")
        print(f"Equalized Odds: {equalized_odds:.4f}")
        print(f"Equalized Opportunity: {equalized_opportunity:.4f}")
        print(f"Predictive Parity: {predictive_parity:.4f}")



    def train_classifier_and_evaluate(self, X_train, X_test, y_train, y_test, sensitive_attr_name):
        """
        Train a simple classifier and compute fairness metrics.
        """
        print("\nTraining a Logistic Regression Classifier...\n")

        # Ensure no missing values in training data
        if X_train.isnull().sum().sum() > 0 or X_test.isnull().sum().sum() > 0:
            print("\nWarning: Missing values detected before training! Filling with 0.")
            X_train.fillna(0, inplace=True)
            X_test.fillna(0, inplace=True)


        # Train logistic regression model
        model = LogisticRegression(solver='liblinear', random_state=42)
        model.fit(X_train, y_train)

        # Predictions
        y_pred_train = model.predict(X_train)
        y_pred_test = model.predict(X_test)

        # Accuracy
        accuracy = accuracy_score(y_test, y_pred_test)
        print(f"Model Accuracy: {accuracy:.4f}")

        # Compute fairness metrics
        self.compute_fairness_metrics(X_test, y_test, y_pred_test, sensitive_attr_name)
