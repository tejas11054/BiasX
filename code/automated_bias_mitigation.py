import os
import pandas as pd
import joblib
from data_preprocessor import process_data
from bias_detector import BiasDetector
from bias_mitigator import BiasMitigator
from sklearn.metrics import confusion_matrix, accuracy_score
from datetime import datetime
import shap
import lime
import lime.lime_tabular
import numpy as np
import glob
import json
import argparse
import sys
sys.stdout.reconfigure(encoding='utf-8')

def save_results_to_json(results_path, dataset, rows, columns, attribute_with_bias, mitigation_technique_used, model_trained, statistical_parity, disparate_impact, equalized_odds, equalized_opportunity, predictive_parity, previous_accuracy, new_accuracy):
    """Save bias mitigation results to a JSON file."""
    results_file = results_path
    
    # Prepare the new entry
    new_entry = {
        "dataset": dataset,
        "rows": rows,
        "columns": columns,
        "Attribute_with_bias": attribute_with_bias,
        "mitigation_technique_used": mitigation_technique_used,
        "model_trained": model_trained,
        "metrics": {
            "statistical_parity": statistical_parity,
            "disparate impact": disparate_impact,
            "equalized_odds": equalized_odds,
            "equalized_opportunity": equalized_opportunity,
            "predictive_parity": predictive_parity
        },
        "Previous_accuracy": previous_accuracy,
        "New_accuracy": new_accuracy,
        "Timestamp": datetime.now().strftime("%d-%m-%Y %H:%M:%S")
    }
    
    # Load existing data if file exists
    if os.path.exists(results_file):
        with open(results_file, "r") as file:
            try:
                results_data = json.load(file)
            except json.JSONDecodeError:
                results_data = []
    else:
        results_data = []
    
    # Append new entry
    results_data.append(new_entry)
    
    # Save updated data
    with open(results_file, "w") as file:
        json.dump(results_data, file, indent=4)

    print(f"✅ Results saved to {results_file}")

# Example integration into your script
# Call this function where mitigation results are finalized:
# save_results_to_json(dataset_name, num_rows, num_columns, sensitive_attr_name, best_method, 'logistic regression', stat_parity, disp_impact, eq_odds, eq_opp, pred_parity, previous_accuracy, mitigated_model_acc)


# Argument parsing
parser = argparse.ArgumentParser(description="Automated Bias Mitigation")
parser.add_argument("dataset_path", help="Path to the dataset CSV file")
parser.add_argument("config_path", help="Path to the config JSON file")
args = parser.parse_args()


# Load config file
with open(args.config_path, "r") as config_file:
    config = json.load(config_file)

DATASET_PATH = args.dataset_path
TARGET_COLUMN = config["target"]
SENSITIVE_ATTRS = config["sensitive_attribute"]
for i in range(len(SENSITIVE_ATTRS)):
    SENSITIVE_ATTRS[i] = SENSITIVE_ATTRS[i].lower()
PRIVILEGED_GROUP = config["privileged_group"]
UNPRIVILEGED_GROUP = config["unprivileged_group"]


# Load dataset
df = pd.read_csv(DATASET_PATH)
rows, columns = df.shape

# Load the trained prediction model and preprocessing objects
model_path = "Model/saved_models"
scaler = joblib.load("Model/scaler.pkl")
label_encoder = joblib.load("Model/label_encoder.pkl")
predictor_models = [joblib.load(os.path.join(model_path, f)) for f in sorted(os.listdir(model_path))]

def get_latest_model_path():
    """Retrieve the latest saved mitigated model path."""
    model_folders = sorted(glob.glob("Mitigated_Model/*"), key=os.path.getmtime, reverse=True)
    if model_folders:
        latest_folder = model_folders[0]
        return os.path.join(latest_folder, "")
    return None


def calculate_explainability_scores(model, X_full, sensitive_attr_name, num_samples=100):
    """Calculate LIME and SHAP scores for the sensitive attribute on a random subset of records."""

    # ✅ Select a random subset of `num_samples` records (without duplicates)
    sample_indices = np.random.choice(len(X_full), min(num_samples, len(X_full)), replace=False)
    X_sampled = X_full.iloc[sample_indices]

    # ✅ Use KernelExplainer for Logistic Regression
    explainer = shap.KernelExplainer(model.predict_proba, X_sampled) if hasattr(model, "predict_proba") else shap.Explainer(model.predict, X_sampled)
    shap_values = explainer.shap_values(X_sampled)

    # ✅ Compute only the mean SHAP importance for the sensitive attribute
    sensitive_index = list(X_full.columns).index(sensitive_attr_name)
    if isinstance(shap_values, list):  # Multi-class case
        shap_importance = np.mean(np.abs(shap_values[1][:, sensitive_index]))
    else:
        shap_importance = np.mean(np.abs(shap_values[:, sensitive_index]))

    # ✅ Compute LIME scores for the sampled records
    lime_explainer = lime.lime_tabular.LimeTabularExplainer(
        training_data=X_full.values,
        feature_names=X_full.columns.tolist(),
        mode="classification"
    )

    lime_scores = []
    for idx in sample_indices:
        lime_exp = lime_explainer.explain_instance(X_full.iloc[idx].values, model.predict_proba)
        lime_score = dict(lime_exp.as_list()).get(sensitive_attr_name, 0)
        lime_scores.append(lime_score)

    avg_lime_importance = np.mean(lime_scores) if lime_scores else 0

    print(f"\n🔍 **Explainability Scores for Sensitive Attribute ('{sensitive_attr_name}')**")
    print(f"📌 Average SHAP Importance (on {num_samples} records): {shap_importance:.4f}")
    print(f"📌 Average LIME Importance (on {num_samples} records): {avg_lime_importance:.4f}")

    return shap_importance, avg_lime_importance



def compute_fairness_metrics(y_test, y_pred, sensitive_attr):
    """Compute Equalized Odds, Equalized Opportunity, and Predictive Parity."""
    privileged_group = (sensitive_attr == 1)
    unprivileged_group = (sensitive_attr == 0)

    def get_confusion_values(y_true, y_pred, group):
        y_true_group = y_true.loc[group] if isinstance(y_true, pd.Series) else y_true[group]
        y_pred_group = y_pred.loc[group] if isinstance(y_pred, pd.Series) else y_pred[group]

        if len(y_true_group) == 0 or len(y_pred_group) == 0:
            return 0, 0, 0, 0

        cm = confusion_matrix(y_true_group, y_pred_group)
        tn, fp, fn, tp = cm.ravel() if cm.shape == (2, 2) else (0, 0, 0, 0)
        return tn, fp, fn, tp

    tn_priv, fp_priv, fn_priv, tp_priv = get_confusion_values(y_test, y_pred, privileged_group)
    tn_unpriv, fp_unpriv, fn_unpriv, tp_unpriv = get_confusion_values(y_test, y_pred, unprivileged_group)

    tpr_priv = tp_priv / (tp_priv + fn_priv) if (tp_priv + fn_priv) > 0 else 0
    fpr_priv = fp_priv / (fp_priv + tn_priv) if (fp_priv + tn_priv) > 0 else 0
    precision_priv = tp_priv / (tp_priv + fp_priv) if (tp_priv + fp_priv) > 0 else 0

    tpr_unpriv = tp_unpriv / (tp_unpriv + fn_unpriv) if (tp_unpriv + fn_unpriv) > 0 else 0
    fpr_unpriv = fp_unpriv / (fp_unpriv + tn_unpriv) if (fp_unpriv + tn_unpriv) > 0 else 0
    precision_unpriv = tp_unpriv / (tp_unpriv + fp_unpriv) if (tp_unpriv + fp_unpriv) > 0 else 0

    equalized_odds = abs(tpr_priv - tpr_unpriv) + abs(fpr_priv - fpr_unpriv)
    equalized_opportunity = abs(tpr_priv - tpr_unpriv)
    predictive_parity = abs(precision_priv - precision_unpriv)

    return equalized_odds, equalized_opportunity, predictive_parity

def get_mitigation_method(bias_metrics):
    """Predict the best bias mitigation method based on bias metrics."""
    X_input = pd.DataFrame([bias_metrics], columns=[
        "statistical_parity_difference", "disparate_impact",
        "positive_rate_privileged", "positive_rate_unprivileged",
        "Equalized Odds", "Equalized Opportunity", "Predictive Parity"
    ])
    
    X_scaled = scaler.transform(X_input)
    predictions = [model.predict(X_scaled)[0] for model in predictor_models]
    predicted_method = max(set(predictions), key=predictions.count)

    return label_encoder.inverse_transform([predicted_method])[0]

def save_mitigated_data(X_train, X_test, y_train, y_test, model, method, name, x_og):
    """Save the mitigated dataset and trained model."""
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    save_folder = f"Mitigated_Model/{name}_{method}_{timestamp}"
    os.makedirs(save_folder, exist_ok=True)

    # Save dataset
    X_train.to_csv(f"{save_folder}/X_train.csv", index=False)
    X_test.to_csv(f"{save_folder}/X_test.csv", index=False)
    y_train.to_csv(f"{save_folder}/y_train.csv", index=False)
    y_test.to_csv(f"{save_folder}/y_test.csv", index=False)
    # Create full dataset
    X_full = pd.concat([X_train, X_test], axis=0)
    y_full = pd.concat([y_train, y_test], axis=0)

    # Add y_full as the last column
    X_full["target"] = y_full.values

    # Save complete dataset
    X_full.to_csv(f"{save_folder}/new_dataset.csv", index=False)
    x_og.to_csv(f"{save_folder}/original_dataset.csv", index=False)

    print(f"🔍 Type of model before saving: {type(model)}")

    # 🔹 No need to call `train_and_evaluate()` here, just save the model
    joblib.dump(model, f"{save_folder}/mitigated_model.pkl")

    print(f"✅ Mitigated dataset and model saved in '{save_folder}/'")

def main():
    dataset_path = DATASET_PATH
    target_column = TARGET_COLUMN
    
    preprocessor, X_train, X_test, y_train, y_test, x_og = process_data(dataset_path, target_column)
    
    if preprocessor:
        X_full = pd.concat([X_train, X_test])
        y_full = pd.concat([y_train, y_test])

        # Pass config values to BiasDetector
        bias_detector = BiasDetector(preprocessor, SENSITIVE_ATTRS, PRIVILEGED_GROUP, UNPRIVILEGED_GROUP)
        sensitive_attr_name, bias_metrics = bias_detector.detect_bias(X_full, y_full)
        
        if sensitive_attr_name:
            bias_mitigator = BiasMitigator(X_train, X_test, y_train, y_test, sensitive_attr_name)
            y_pred, prev_acc = bias_mitigator.train_and_evaluate(X_train, y_train, X_test, y_test)
            
            equalized_odds, equalized_opportunity, predictive_parity = compute_fairness_metrics(
                y_test, y_pred, X_test[sensitive_attr_name]
            )
            
            bias_metrics.update({
                "Equalized Odds": equalized_odds,
                "Equalized Opportunity": equalized_opportunity,
                "Predictive Parity": predictive_parity
            })
            
            best_method = get_mitigation_method(list(bias_metrics.values()))
            print(f"\n🚀 Selected Bias Mitigation Technique: {best_method}")

            # Apply the selected bias mitigation technique
            # Apply bias mitigation
            mitigation_function = getattr(bias_mitigator, best_method, None)
            if mitigation_function:
                print(f"\nApplying {best_method}...\n")
                y_mitigated_pred, mitigated_model_acc, mitigated_model = mitigation_function()  # 🔹 Get the trained model

                print(f"\n✅ Mitigated Model Accuracy: {mitigated_model_acc:.4f}")

                # Save mitigated dataset and trained model
                save_mitigated_data(X_train, X_test, y_train, y_test, mitigated_model, best_method, dataset_path, x_og)

                # Load the latest saved model and predict on X_full
                latest_model_path = get_latest_model_path() 
                latest_model_path = rf"{latest_model_path}/mitigated_model.pkl"
                if latest_model_path:
                    mitigated_model = joblib.load(latest_model_path)
                    y_pred_full = mitigated_model.predict(X_full)

                    #shap_score, lime_score = calculate_explainability_scores(mitigated_model, X_full, sensitive_attr_name)

                    #print(f"\n🔍 SHAP Score for {sensitive_attr_name}: {shap_score:.4f}")
                    #print(f"\n🔍 LIME Score for {sensitive_attr_name}: {lime_score:.4f}")

            else:
                print("⚠️ Error: Selected method is not available in BiasMitigator.")

    print(bias_metrics)

    json_save_path = os.path.join(get_latest_model_path(), "results.json")
    print("JSON SAVE PATH = ", json_save_path)
    save_results_to_json(results_path=json_save_path,
                         dataset=DATASET_PATH, 
                         rows=rows, 
                         columns=columns, 
                         attribute_with_bias=sensitive_attr_name, 
                         mitigation_technique_used=best_method, 
                         model_trained="Logistic Regression", 
                         statistical_parity=bias_metrics['statistical_parity_difference'], 
                         disparate_impact=bias_metrics['disparate_impact'], 
                         equalized_odds=bias_metrics['Equalized Odds'], 
                         equalized_opportunity=bias_metrics['Equalized Opportunity'], 
                         predictive_parity=bias_metrics['Predictive Parity'], 
                         previous_accuracy=prev_acc, 
                         new_accuracy=mitigated_model_acc)

if __name__ == "__main__":
    main()
