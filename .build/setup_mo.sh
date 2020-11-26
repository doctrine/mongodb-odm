#!/bin/bash

echo Loading MO for $DEPLOYMENT

mongo-orchestration --version

if [[ -z $TRAVIS_BUILD_DIR ]]; then
    export BUILD_DIR=`pwd`;
else
    export BUILD_DIR=$TRAVIS_BUILD_DIR;
fi

case $DEPLOYMENT in
  SHARDED_CLUSTER)
    ${BUILD_DIR}/.build/mo.sh ${BUILD_DIR}/.build/configurations/sharded_clusters/cluster.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri, "/?retryWrites=false";' > /tmp/uri.txt
    ;;
  SHARDED_CLUSTER_RS)
    ${BUILD_DIR}/.build/mo.sh ${BUILD_DIR}/.build/configurations/sharded_clusters/cluster_replset.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri;' > /tmp/uri.txt
    ;;
  STANDALONE_AUTH)
    ${BUILD_DIR}/.build/mo.sh ${BUILD_DIR}/.build/configurations/standalone/standalone-auth.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_auth_uri;' > /tmp/uri.txt
    ;;
  REPLICASET)
    ${BUILD_DIR}/.build/mo.sh ${BUILD_DIR}/.build/configurations/replica_sets/replicaset.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri;' > /tmp/uri.txt
    ;;
  *)
    ${BUILD_DIR}/.build/mo.sh ${BUILD_DIR}/.build/configurations/standalone/standalone.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri;' > /tmp/uri.txt
    ;;
esac

echo -n "MongoDB Test URI: "
cat /tmp/uri.txt
echo

echo "Raw MO Response:"
cat /tmp/mo-result.json

echo
