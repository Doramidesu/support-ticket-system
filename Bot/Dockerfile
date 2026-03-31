FROM node:22-alpine
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY index.js ./
RUN mkdir -p /app/PuskomSesi
EXPOSE 3000
CMD ["npm", "start"]
