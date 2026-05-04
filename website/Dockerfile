FROM nginx:1.29.8-alpine

# Remove default nginx page
RUN rm -rf /usr/share/nginx/html/*

# Copy static files
COPY web/ /usr/share/nginx/html/

# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
