FROM node:22.13.1-bookworm-slim

RUN npm install --global --force corepack@0.34.0 \
    && corepack prepare pnpm@11.19.0 --activate

WORKDIR /workspace
