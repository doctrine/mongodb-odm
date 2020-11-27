#!/bin/bash

if nc -z localhost 27017
then
  sudo service mongod stop
fi

export SERVER_FILENAME=mongodb-linux-x86_64-${SERVER_DISTRO}-${SERVER_VERSION}
wget -qO- http://fastdl.mongodb.org/linux/${SERVER_FILENAME}.tgz | tar xz
export PATH=${PWD}/${SERVER_FILENAME}/bin:${PATH}
mongod --version
