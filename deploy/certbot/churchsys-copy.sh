#!/bin/bash
# Deploy hook for certbot renewal: copy renewed certs into nginx ssl mount and reload
cp -L /etc/letsencrypt/live/churchsys.cmals.org/fullchain.pem /home/ubuntu/churchsys/docker/nginx/ssl/fullchain.pem
cp -L /etc/letsencrypt/live/churchsys.cmals.org/privkey.pem /home/ubuntu/churchsys/docker/nginx/ssl/privkey.pem
chown ubuntu:ubuntu /home/ubuntu/churchsys/docker/nginx/ssl/*.pem
chmod 644 /home/ubuntu/churchsys/docker/nginx/ssl/fullchain.pem
chmod 600 /home/ubuntu/churchsys/docker/nginx/ssl/privkey.pem
docker exec churchsys-web nginx -s reload
