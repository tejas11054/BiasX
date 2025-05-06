import numpy as np
import pandas as pd
import logging
from aif360.algorithms.preprocessing import Reweighing, DisparateImpactRemover
from aif360.algorithms.inprocessing import PrejudiceRemover, AdversarialDebiasing
from aif360.datasets import StandardDataset
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score
import tensorflow.compat.v1 as tf

tf.compat.v1.disable_eager_execution()


class BiasMitigator:
    def __init__(self, X_train, X_test, y_train, y_test, sensitive_attr_name):
        self.X_train = X_train
        self.X_test = X_test
        self.y_train = y_train
        self.y_test = y_test
        self.sensitive_attr_name = sensitive_attr_name

        logging.basicConfig(level=logging.INFO)
        self.logger = logging.getLogger(__name__)

    def convert_to_aif360_dataset(self, X, y):
        """Convert Pandas DataFrame to AIF360 StandardDataset."""
        df = pd.concat([X, y], axis=1)
        df[self.sensitive_attr_name] = df[self.sensitive_attr_name].astype(int)
        
        return StandardDataset(
            df,
            label_name=y.name,
            protected_attribute_names=[self.sensitive_attr_name],
            favorable_classes=[1],
            privileged_classes=[[1]]
        )

    def train_and_evaluate(self, X_train, y_train, X_test, y_test):
        """Train Logistic Regression and evaluate accuracy."""
        model = LogisticRegression(solver='liblinear', random_state=42)
        model.fit(X_train, y_train)
        
        # 🔹 Store trained model inside the class
        self.trained_model = model  

        y_pred = model.predict(X_test)
        acc = accuracy_score(y_test, y_pred)
        print(f"Model Accuracy (mitigator file): {acc:.4f}")
        
        return y_pred, acc

    def reweighting(self):
        """Apply Reweighting Preprocessing Technique"""
        print("\nApplying Reweighting Bias Mitigation...")
        dataset_train = self.convert_to_aif360_dataset(self.X_train, self.y_train)
        dataset_test = self.convert_to_aif360_dataset(self.X_test, self.y_test)

        reweighing = Reweighing(
            unprivileged_groups=[{self.sensitive_attr_name: 0}],
            privileged_groups=[{self.sensitive_attr_name: 1}]
        )
        dataset_train_transf = reweighing.fit_transform(dataset_train)

        y_pred, acc = self.train_and_evaluate(dataset_train_transf.features, dataset_train_transf.labels.ravel(),
                                       dataset_test.features, dataset_test.labels.ravel())
        return y_pred, acc, self.trained_model


    def disparate_impact_remover(self):
        """Apply Disparate Impact Remover Preprocessing Technique"""
        print("\nApplying Disparate Impact Remover...")
        dataset_train = self.convert_to_aif360_dataset(self.X_train, self.y_train)
        dataset_test = self.convert_to_aif360_dataset(self.X_test, self.y_test)

        di_remover = DisparateImpactRemover(repair_level=1.0)
        dataset_train_transf = di_remover.fit_transform(dataset_train)

        y_pred, acc =  self.train_and_evaluate(dataset_train_transf.features, dataset_train_transf.labels.ravel(),
                                       dataset_test.features, dataset_test.labels.ravel())
        return y_pred, acc, self.trained_model

    def adversarial_debiasing(self):
        """Apply Adversarial Debiasing Inprocessing Technique"""
        print("\nApplying Adversarial Debiasing...")
        dataset_train = self.convert_to_aif360_dataset(self.X_train, self.y_train)
        dataset_test = self.convert_to_aif360_dataset(self.X_test, self.y_test)

        privileged_groups = [{self.sensitive_attr_name: 1}]
        unprivileged_groups = [{self.sensitive_attr_name: 0}]

        # Reset TensorFlow graph to avoid conflicts
        tf.reset_default_graph()
        sess = tf.Session()

        adv_debias = AdversarialDebiasing(
            privileged_groups=privileged_groups,
            unprivileged_groups=unprivileged_groups,
            scope_name='adv_debiasing',
            sess=sess
        )
        adv_debias.fit(dataset_train)
        dataset_test_pred = adv_debias.predict(dataset_test)
        y_pred = dataset_test_pred.labels.ravel()
        acc = accuracy_score(self.y_test, y_pred)
        sess.close()

        return y_pred, acc, self.trained_model

    def prejudice_remover(self):
        """Apply Prejudice Remover Inprocessing Technique"""
        print("\nApplying Prejudice Remover...")
        dataset_train = self.convert_to_aif360_dataset(self.X_train, self.y_train)
        dataset_test = self.convert_to_aif360_dataset(self.X_test, self.y_test)

        pr = PrejudiceRemover(eta=1.0)
        pr.fit(dataset_train)
        dataset_test_pred = pr.predict(dataset_test)
        y_pred = dataset_test_pred.labels.ravel()
        acc = accuracy_score(self.y_test, y_pred)
        return y_pred, acc, self.trained_model

    def apply_all_mitigations(self):
        """Apply all bias mitigation techniques and display results"""
        mitigations = {
            "Reweighting": self.reweighting,
            "Disparate Impact Remover": self.disparate_impact_remover,
            "Adversarial Debiasing": self.adversarial_debiasing,
            "Prejudice Remover": self.prejudice_remover
        }

        results = {}
        for method, function in mitigations.items():
            print(f"\n{'='*30}\nApplying {method}\n{'='*30}")
            y_pred, acc = function()
            results[method] = acc

        print("\nFinal Bias Mitigation Results:")
        for method, acc in results.items():
            print(f"{method}: Accuracy = {acc:.4f}")
