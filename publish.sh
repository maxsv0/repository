#!/bin/bash

if [ -z "$1" ]
  then
    echo "Missing field: Module name"
	exit 0
fi


if [ -z "$2" ]
  then
    echo "Missing field: repository KEY"
	exit 0
fi


if [ -z "$3" ]
  then
    echo "Missing field: Module Title"
	exit 0
fi


if [ -z "$4" ]
  then
    echo "Missing field: Module Version"
	exit 0
fi


if [ -z "$5" ]
  then
    echo "Missing field: Released Date"
	exit 0
fi


if [ -z "$6" ]
  then
    echo "Missing field: Description"
	exit 0
fi


if [ -z "$7" ]
  then
    echo "Missing field: Tags"
	exit 0
fi


if [ ! -f preview.jpg ];
	then
      echo "'preview.jpg' not found"
	  exit 0
fi


echo "Publish to  to MSV repository: rep.msvhost.com"

echo "========> $1 (key :  $2)"
echo "Title: $3"
echo "Version: $4"
echo "Date: $5"
echo "Description: $6"
echo "Tags: $7"
echo "======================="

mkdir src-temp
cp -a src/. src-temp
find src-temp/ -name .DS_Store -delete

echo "Creating archive.zip.."
cd src-temp
zip -r ../archive.zip .
cd ..

echo "#file list">filelist.txt
echo "#module">>filelist.txt
find src-temp/module -type f -regex "^.*$">>filelist.txt
echo "#content">>filelist.txt
find src-temp/content -type f -regex "^.*$">>filelist.txt
echo "#template">>filelist.txt
find src-temp/template -type f -regex "^.*$">>filelist.txt


echo "Sending file to repository.."
curl -F "file=@archive.zip"  -F "preview=@preview.jpg"  -F "filelist=@filelist.txt"  -F "module=$1" -F "key=$2" -F "title=$3" -F "version=$4" -F "released=$5" -F "description=$6"  -F "tags=$7" http://rep.msvhost.com/api/import/
echo "Done!"

echo "Removing temp files.."
rm -R src-temp
rm archive.zip
rm filelist.txt
echo "Done!"

exit 0