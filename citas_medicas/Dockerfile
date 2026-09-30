FROM node:24-alpine AS build

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build -- --configuration production --output-path=/app/dist

FROM nginx:alpine

COPY --from=build /app/dist/browser/ /usr/share/nginx/html/

RUN printf '%s\n' 'server {' \
	'  listen 80;' \
	'  server_name _;' \
	'  root /usr/share/nginx/html;' \
	'  index index.html;' \
	'  location / {' \
	'    try_files $uri $uri/ /index.html;' \
	'  }' \
	'}' > /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]