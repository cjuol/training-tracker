FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1

WORKDIR /app

# pyproject.toml + source arrive in Phase 4.
# For Phase 0, this image is a base only. Build succeeds without app code;
# the CMD will fail until Phase 4 lands `src/sidecar/main.py`. docker-compose
# keeps restarting with backoff, which is fine — `make test-py` / health check
# will only pass after Phase 4.

EXPOSE 8001
CMD ["sh", "-c", "if [ -f pyproject.toml ]; then pip install -e '.[dev]' && exec uvicorn sidecar.main:app --host 0.0.0.0 --port 8001 --reload; else echo 'sidecar pyproject.toml not yet present (Phase 4 pending)'; sleep infinity; fi"]
