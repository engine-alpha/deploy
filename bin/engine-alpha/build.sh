#!/usr/bin/env bash

# Display all executed commands
set -x

# Temporary directory to clone repository
TEMP_DIR=`mktemp -d`

# Deploy directory
DEST_DIR=/var/www/git.engine-alpha.org/downloads

# Git reference to build. Either a tag or "master", defaults to "master"
GIT_REF=${GIT_REF:-master}

# Clean up temporary directory
function cleanup {
    rm -rf ${TEMP_DIR}
}

# Check whether we're interested in building that reference at all ...
if [[ ${GIT_REF} =~ ^v[0-9]+\.[0-9]+(?:\.[0-9]+)?$ ]]; then
    echo "Packaging release ... "
elif [[ ${GIT_REF} = "master" ]]; then
    echo "Packaging nightly ... "
else
    echo "Aborting, because we're not interested in that reference."
    exit 0
fi

# Ensure the deploy directory exists
mkdir -p ${DEST_DIR}

# Clone always a fresh copy ...
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

if [[ $? -ne 0 ]]; then
    echo "Build failed, aborting ... "
    cleanup

    exit 1
fi

# Delete maybe existing older versions
rm ${DEST_DIR}/${GIT_REF}/engine-alpha.jar
rm ${DEST_DIR}/${GIT_REF}/engine-alpha-docs.zip
rm ${DEST_DIR}/engine-alpha.pdf

# Move JAR into final destination
mkdir -p ${DEST_DIR}/${GIT_REF}
cp build/Engine.Alpha.jar ${DEST_DIR}/${GIT_REF}/engine-alpha.jar

# --------------------------------------- #
# ----- BUILD DOCUMENTATION ARCHIVE ----- #
# --------------------------------------- #

ant docs

if [[ $? -ne 0 ]]; then
    echo "Build failed, aborting ... "
    cleanup

    exit 1
fi

cd doc
zip -r ${DEST_DIR}/${GIT_REF}/engine-alpha-docs.zip *
cd -

# -------------------------------------- #
# ----- PACKAGE MARKETING MATERIAL ----- #
# -------------------------------------- #

wget -O ${DEST_DIR}/engine-alpha.pdf https://github.com/engine-alpha/marketing/raw/master/Engine%20Alpha.pdf

# ---------------------------------- #
# ----- BUILD COMBINED ARCHIVE ----- #
# ---------------------------------- #

cd ${DEST_DIR}/${GIT_REF}
zip -r ../${GIT_REF}.zip *
cd -

cleanup