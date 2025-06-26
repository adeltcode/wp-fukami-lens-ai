import os

class Config:
    def __init__(self):
        self.max_input_tokens = int(os.environ.get("NPA_RAG_MAX_INPUT_TOKENS", 8191))
        self.embeddings_model = os.environ.get("NPA_RAG_EMBEDDINGS_MODEL", "text-embedding-3-small")

def load_config():
    return Config() 