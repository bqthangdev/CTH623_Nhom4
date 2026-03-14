from contextlib import asynccontextmanager
from pathlib import Path

from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routers import recommendations, visual_search

# Load the Laravel .env from the project root (one level above ai-service/)
load_dotenv(dotenv_path=Path(__file__).parent.parent / ".env")


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Load CLIP model first — required before computing or comparing embeddings.
    # On first run this downloads the model weights (~350 MB for ViT-B/32).
    visual_search.load_clip_model()
    # Pre-compute CLIP text embeddings for zero-shot object classification.
    visual_search.load_category_labels()
    # Load stored product embeddings from the database into memory.
    visual_search.load_embeddings()
    yield


app = FastAPI(title="SmartShop AI Service", version="1.0.0", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(visual_search.router, prefix="/api")
app.include_router(recommendations.router, prefix="/api")


@app.get("/health")
def health():
    return {"status": "ok"}
