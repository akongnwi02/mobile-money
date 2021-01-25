# Mobile Money API

This API aggregates mobile money providers in cameroon and serves them via a single API

## Services

- Orange Cash in / Cash out
- MTN MoMo Cash in / Cash out

## Local Development

Check the Makefile file at the root for instructions how to start the application in development mode

## Deployment

- Deployment is done on heroku platform inside a laradock docker container

- Image with built with Dockerfile_Deploy file at the root of the project `docker build . -f Dockerfile_Deploy -t momocm`

- Configure the application's internal port by passing an environment variable $PORT when starting the container (Heroku assigns a port dynamically as well when bringing up the container) 

## API request

Check the rest/momo.http file for sample requests