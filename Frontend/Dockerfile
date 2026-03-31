# Build stage
FROM node:22-alpine as builder
WORKDIR /app
# Copy package files
COPY package*.json ./
# Install dependencies
RUN npm install
# Copy source files
COPY . .
ENV VITE_API_URL="VITE_API_URL_PLACEHOLDER"
# Build application
RUN npm run build


# Production stage
FROM nginx:alpine as production
# Copy built assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html
# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --chmod=755 entrypoint.sh /entrypoint.sh
# Expose port 80
EXPOSE 80
entrypoint ["/entrypoint.sh"]
