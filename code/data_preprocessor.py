import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.impute import SimpleImputer
from sklearn.model_selection import train_test_split
import logging
import os

class DataPreprocessor:
    def __init__(self, categorical_columns=None, numerical_columns=None, target_column='target'):
        self.categorical_columns = categorical_columns
        self.numerical_columns = numerical_columns
        self.target_column = target_column
        
        # Initialize preprocessing objects
        self.num_imputer = SimpleImputer(strategy='median')
        self.cat_imputer = SimpleImputer(strategy='most_frequent')
        self.scaler = StandardScaler()
        self.label_encoders = {}
        self.target_encoder = LabelEncoder()
        
        # Store original categorical values and their mappings
        self.categorical_mappings = {}
        
        logging.basicConfig(level=logging.INFO)
        self.logger = logging.getLogger(__name__)

    def detect_column_types(self, df):
        """
        Automatically detect numerical and categorical columns.
        """
        if self.numerical_columns is None:
            numeric_dtypes = df.select_dtypes(include=['int64', 'float64'])
            self.numerical_columns = [col for col in numeric_dtypes.columns 
                                    if col != self.target_column and df[col].notna().any()]
        
        if self.categorical_columns is None:
            categorical_dtypes = df.select_dtypes(include=['object', 'category'])
            self.categorical_columns = [col for col in categorical_dtypes.columns 
                                      if col != self.target_column and df[col].notna().any()]
        
        self.logger.info(f"Detected {len(self.numerical_columns)} numerical columns")
        self.logger.info(f"Detected {len(self.categorical_columns)} categorical columns")

    def handle_missing_values(self, df):
        """
        Handle missing values in both numerical and categorical columns.
        """
        df_processed = df.copy()
        
        # Handle numerical missing values
        if self.numerical_columns:
            valid_num_cols = [col for col in self.numerical_columns if df[col].notna().any()]
            if valid_num_cols:
                df_processed[valid_num_cols] = self.num_imputer.fit_transform(df[valid_num_cols])
        
        # Handle categorical missing values
        if self.categorical_columns:
            valid_cat_cols = [col for col in self.categorical_columns if df[col].notna().any()]
            if valid_cat_cols:
                df_processed[valid_cat_cols] = self.cat_imputer.fit_transform(df[valid_cat_cols])
        
        self.logger.info("Handled missing values")
        return df_processed

    def handle_outliers(self, df, method='iqr', threshold=1.5):
        """
        Handle outliers in numerical columns.
        """
        df_processed = df.copy()
        
        for col in self.numerical_columns:
            if not df[col].notna().any():
                continue
                
            if method == 'iqr':
                Q1 = df[col].quantile(0.25)
                Q3 = df[col].quantile(0.75)
                IQR = Q3 - Q1
                lower_bound = Q1 - threshold * IQR
                upper_bound = Q3 + threshold * IQR
                df_processed[col] = df[col].clip(lower=lower_bound, upper=upper_bound)
            
            elif method == 'zscore':
                z_scores = np.abs((df[col] - df[col].mean()) / df[col].std())
                df_processed[col] = df[col].mask(z_scores > threshold, df[col].median())
        
        self.logger.info(f"Handled outliers using {method} method")
        return df_processed

    def encode_and_normalize_categorical(self, df):
        """
        Encode categorical variables and store mappings.
        """
        if not self.categorical_columns:
            return df
        
        df_processed = df.copy()
        valid_cat_cols = [col for col in self.categorical_columns if df[col].notna().any()]
        
        for col in valid_cat_cols:
            # Store original values before encoding
            original_values = df[col].unique()
            self.categorical_mappings[col] = {
                'original_values': list(original_values),
                'encoded_values': {},
                'inverse_mapping': {}
            }
            
            # Create label encoder for this column
            le = LabelEncoder()
            encoded_values = le.fit_transform(df[col].astype(str))
            
            # Store mappings
            self.label_encoders[col] = le
            for orig, enc in zip(le.classes_, range(len(le.classes_))):
                self.categorical_mappings[col]['encoded_values'][orig] = enc
                self.categorical_mappings[col]['inverse_mapping'][enc] = orig
            
            df_processed[col] = encoded_values
        
        self.logger.info("Encoded categorical variables")
        return df_processed
    
    def get_original_values(self, column_name):
        """Get original categorical values for a column."""
        if column_name in self.categorical_mappings:
            return self.categorical_mappings[column_name]['original_values']
        return None

    def get_normalized_to_original_mapping(self, column_name):
        """Get mapping from normalized values to original values."""
        if column_name in self.categorical_mappings:
            return self.categorical_mappings[column_name]['normalized_mapping']
        return None
    

    def scale_numerical_features(self, df):
        """
        Scale numerical features using StandardScaler.
        """
        df_processed = df.copy()
        
        if self.numerical_columns:
            valid_num_cols = [col for col in self.numerical_columns if df[col].notna().any()]
            if valid_num_cols:
                df_processed[valid_num_cols] = self.scaler.fit_transform(df[valid_num_cols])
                self.logger.info("Scaled numerical features")
        
        return df_processed

    def process_data(self, df):
        """
        Execute the complete preprocessing pipeline.
        """
        self.logger.info("Starting data preprocessing pipeline...")
        
        # Verify target column exists
        if self.target_column not in df.columns:
            raise ValueError(f"Target column '{self.target_column}' not found in the dataset")
        
        # Store and encode target column
        target = df[self.target_column].copy()
        if target.dtype == object or target.dtype.name == 'category':
            self.logger.info("Encoding categorical target variable")
            target = self.target_encoder.fit_transform(target)
        
        features = df.drop(columns=[self.target_column])
        
        # Detect column types
        self.detect_column_types(df)
        
        # Process features
        processed_features = features.copy()
        processed_features = self.handle_missing_values(processed_features)
        processed_features = self.handle_outliers(processed_features)
        processed_features = self.encode_and_normalize_categorical(processed_features)
        processed_features = self.scale_numerical_features(processed_features)
        
        # Add encoded target column back
        processed_data = processed_features.copy()
        processed_data[self.target_column] = target
        
        self.logger.info("Completed data preprocessing pipeline")
        return processed_data

def process_data(dataset_name, target_column_name):
    """
    Process the data with improved encoding handling and lowercase column names.
    """
    # Load data and convert column names to lowercase
    data = pd.read_csv(f"{dataset_name}")
    data.columns = data.columns.str.lower()  # Convert all column names to lowercase
    target_column_name = target_column_name.lower()  # Ensure the target column is also lowercase
    
    # Initialize preprocessor
    preprocessor = DataPreprocessor(target_column=target_column_name)
    
    try:
        # Process the data
        processed_data = preprocessor.process_data(data)
        
        # Keep original values for sensitive attributes
        sensitive_cols = ['race', 'sex', 'gender']  # Add any other potential sensitive attributes
        original_sensitive_data = {
            col: data[col] for col in sensitive_cols if col in data.columns
        }
        
        # Split the data
        X = processed_data.drop(columns=[preprocessor.target_column])
        y = processed_data[preprocessor.target_column]
        
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        
        print("Data preprocessing completed successfully!")
        print(f"Training set shape: {X_train.shape}")
        print(f"Testing set shape: {X_test.shape}")
        
        # Print target encoding
        if hasattr(preprocessor.target_encoder, 'classes_'):
            print("\nTarget variable encoding:")
            for i, label in enumerate(preprocessor.target_encoder.classes_):
                print(f"{label} -> {i}")
        # Check for missing values after preprocessing
        if X.isnull().sum().sum() > 0:
            print("\nWarning: Missing values detected in processed dataset! Applying imputation.")
            X.fillna(0, inplace=True)  # Replace remaining NaNs with 0

        # Check for missing values again in train/test splits
        if X_train.isnull().sum().sum() > 0 or X_test.isnull().sum().sum() > 0:
            print("\nWarning: Missing values detected in train/test sets! Applying imputation.")
            X_train.fillna(0, inplace=True)
            X_test.fillna(0, inplace=True)
        return preprocessor, X_train, X_test, y_train, y_test, data
        
    except Exception as e:
        print(f"An error occurred: {str(e)}")
        return None, None, None, None, None

