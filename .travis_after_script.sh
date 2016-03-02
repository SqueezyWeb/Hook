#!/bin/bash

echo "--DEBUG--"
echo "TRAVIS_REPO_SLUG: $TRAVIS_REPO_SLUG"
echo "TRAVIS_PHP_VERSION: $TRAVIS_PHP_VERSION"
echo "TRAVIS_PULL_REQUEST: $TRAVIS_PULL_REQUEST"

if [ "$TRAVIS_BRANCH" == "develop" ] || [ "$TRAVIS_BRANCH" == "master" ]; then
  if [ "$TRAVIS_PULL_REQUEST" == false ] && [ "$TRAVIS_PHP_VERSION" == "5.5" ]; then
    echo -e "Publishing PHPDoc...\n"

    cp -R docs $HOME/docs-latest
    cp -R coverage $HOME/coverage-latest

    cd $HOME
    git config --global user.email 'mattia@squeezyweb.com'
    git config --global user.name "Travis CI"
    git clone --quiet --branch=gh-pages https://${GH_TOKEN}@github.com/SqueezyWeb/Hook.git gh-pages > /dev/null

    cd gh-pages
    echo "--DEBUG : Suppression"
    git rm -rf docs/$TRAVIS_BRANCH

    echo "--DEBUG : Dossier"
    mkdir -p docs/$TRAVIS_BRANCH
    mkdir -p coverage/$TRAVIS_BRANCH

    echo "--DEBUG : Copie"
    cp -Rf $HOME/docs-latest/* docs/$TRAVIS_BRANCH/
    cp -Rf $HOME/coverage-latest/* coverage/$TRAVIS_BRANCH/

    echo "--DEBUG : git"
    git add -f .
    git commit -m "phpDocumentor (Travis Build: $TRAVIS_BUILD_NUMBER - Branch $TRAVIS_BRANCH)"
    git push -fq https://${GH_TOKEN}@github.com/SqueezyWeb/Hook.git ${TRAVIS_BRANCH}:gh-pages > /dev/null 2>&1

    echo -e "Published phpDoc to gh-pages.\n"
  fi
fi
