# How do I install Engelsystem on Docker
## Install with Docker on Ubuntu:
* prerequisities 
    * docker requires a 64-bit installation
    * kernel must be 3.10 or above ( uname -r)
*To upgrade your kernel and install the additional packages, do the following:
* Update your package manager & Login as a Root.
```
$ sudo apt-get update
```
* Install both the required and optional packages.
```
sudo apt-get install linux-image-generic-lts-trusty
sudo apt-get install linux-image-extra-`uname -r`
```
* Go ahead and Get the latest version of docker:
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install linux-image-extra-`uname -r`
sudo apt-key adv --keyserver hkp://pgp.mit.edu:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D
echo "deb https://apt.dockerproject.org/repo ubuntu-trusty main" | sudo tee /etc/apt/sources.list.d/docker.list
sudo apt-get update
sudo apt-get install docker-engine
```
* Docker run:

```sudo docker run -p sreeja1125/fossasia-engelsystem```

