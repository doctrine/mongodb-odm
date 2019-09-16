#!/bin/bash

echo Loading MO for $DEPLOYMENT

mongo-orchestration --version

if [[ -z $TRAVIS_BUILD_DIR ]]; then
    export TRAVIS_BUILD_DIR=`pwd`;
fi

case $DEPLOYMENT in
  SHARDED_CLUSTER)
    ${TRAVIS_BUILD_DIR}/.travis/mo.sh ${TRAVIS_BUILD_DIR}/.travis/configurations/sharded_clusters/cluster.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri, "/?retryWrites=false";' > /tmp/uri.txt
    ;;
  SHARDED_CLUSTER_RS)
    ${TRAVIS_BUILD_DIR}/.travis/mo.sh ${TRAVIS_BUILD_DIR}/.travis/configurations/sharded_clusters/cluster_replset.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri;' > /tmp/uri.txt
    ;;
  STANDALONE_AUTH)
    ${TRAVIS_BUILD_DIR}/.travis/mo.sh ${TRAVIS_BUILD_DIR}/.travis/configurations/standalone/standalone-auth.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_auth_uri;' > /tmp/uri.txt
    ;;
  REPLICASET)
    ${TRAVIS_BUILD_DIR}/.travis/mo.sh ${TRAVIS_BUILD_DIR}/.travis/configurations/replica_sets/replicaset.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri;' > /tmp/uri.txt
    ;;
  *)
    ${TRAVIS_BUILD_DIR}/.travis/mo.sh ${TRAVIS_BUILD_DIR}/.travis/configurations/standalone/standalone.json start > /tmp/mo-result.json
    cat /tmp/mo-result.json | tail -n 1 | php -r 'echo json_decode(file_get_contents("php://stdin"))->mongodb_uri;' > /tmp/uri.txt
    ;;
esac

echo -n "MongoDB Test URI: "
cat /tmp/uri.txt
echo

echo "Raw MO Response:"
cat /tmp/mo-result.json

echo
