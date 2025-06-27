from utils.tokenizer import OpenAITokenizerWrapper

MODEL_TO_ENCODING = {
    "text-embedding-3-small": "cl100k_base",
    "text-embedding-3-large": "cl100k_base",
    "text-embedding-ada-002": "cl100k_base",
    "gpt-3.5-turbo": "cl100k_base",
    "gpt-4": "cl100k_base",
    # Add more mappings as needed
}

def get_tokenizer(model_name):
    encoding = MODEL_TO_ENCODING.get(model_name, "cl100k_base")
    return OpenAITokenizerWrapper(model_name=encoding) 