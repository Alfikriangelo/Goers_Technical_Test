# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 — Build the Vite bundle
# ---------------------------------------------------------------------------
FROM node:22-alpine AS build

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2 — Serve the bundle with nginx
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS runtime

COPY frontend/docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
