from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routers import visual_search, recommendations

app = FastAPI(title="SmartShop AI Service", version="1.0.0")

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
