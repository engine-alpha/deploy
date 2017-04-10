#!/usr/bin/env bash

# Clean up temporary directory
function cleanup {
    echo -n "Cleaning up ... "
    rm -rf ${TEMP_DIR}
    echo "Done."
}

set -x # Display all executed commands, enable when debugging
set -e # Exit on error
set -u # Don't allow undeclared variables
set -o pipefail

# Temporary directory to clone repository
TEMP_DIR=`mktemp -d`
trap cleanup INT TERM EXIT

# Deploy directory
DEST_DIR=/var/www/git.engine-alpha.org/downloads
DOCS_DIR=/var/www/docs.engine-alpha.org

# Git reference to build. Either a tag or "master", defaults to "master"
GIT_REF=${GIT_REF:-master}

# Check whether we're interested in building that reference at all ...
if [[ ${GIT_REF} =~ ^v[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
    echo "Packaging release ... "
elif [[ ${GIT_REF} = "master" ]]; then
    echo "Packaging nightly ... "
else
    echo "Aborting, because we're not interested in that reference."
    exit 0
fi

# Ensure the deploy directory exists
mkdir -p ${DEST_DIR}

# Always clone a fresh copy ...
git clone https://github.com/engine-alpha/engine-alpha ${TEMP_DIR}/engine-alpha
cd ${TEMP_DIR}/engine-alpha

# Checkout the relevant reference
git checkout ${GIT_REF}

# Provide version information for the JAR
mkdir -p res
git rev-parse HEAD > res/commit
git describe --tags || echo ${GIT_REF} > res/version

# ------------------------------ #
# ----- BUILD RUNNABLE JAR ----- #
# ------------------------------ #

ant jar

# Delete maybe existing older versions
rm -f ${DEST_DIR}/${GIT_REF}/engine-alpha.jar
rm -f ${DEST_DIR}/${GIT_REF}/engine-alpha-docs.zip
rm -f ${DEST_DIR}/engine-alpha.pdf

# Move JAR into final destination
mkdir -p ${DEST_DIR}/${GIT_REF}
cp build/Engine.Alpha.jar ${DEST_DIR}/${GIT_REF}/engine-alpha.jar

# --------------------------------------- #
# ----- BUILD DOCUMENTATION ARCHIVE ----- #
# --------------------------------------- #

ant docs

cd doc
zip -r ${DEST_DIR}/${GIT_REF}/engine-alpha-docs.zip *
cd -

mkdir -p ${DOCS_DIR}/${GIT_REF}
cp -rpv doc/* ${DOCS_DIR}/${GIT_REF}

# -------------------------------------- #
# ----- PACKAGE MARKETING MATERIAL ----- #
# -------------------------------------- #

wget -O ${DEST_DIR}/${GIT_REF}/engine-alpha.pdf https://github.com/engine-alpha/marketing/raw/master/Engine%20Alpha.pdf

# ---------------------------------- #
# ----- BUILD COMBINED ARCHIVE ----- #
# ---------------------------------- #

cd ${DEST_DIR}/${GIT_REF}
zip -r ${DEST_DIR}/${GIT_REF}.zip *
cd -