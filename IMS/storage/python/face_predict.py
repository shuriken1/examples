#!/usr/bin/env python

import sys
import numpy as np
import cv2 as cv
import json

file_root = '/var/www/html/storage/files/training/test/'
#print(sys.argv[1])
file_name = sys.argv[1] if len(sys.argv) > 1 else None

recogniser = cv.face.LBPHFaceRecognizer_create()
#recogniser.setThreshold(110)
collector = cv.face.StandardCollector_create()
recogniser.read('/var/www/html/storage/files/training/trainer.yml')
face_cascade = cv.CascadeClassifier('/var/www/html/storage/python/face.xml')
font = cv.FONT_HERSHEY_SIMPLEX

names = ['None', 'Laura', 'Alice', 'X', 'Y', 'Z']

if file_name == None:
  print('No file name')
  sys.exit()

file_path = file_root + file_name + '.jpg'
#print(file_path)
img = cv.imread(file_root + file_name + '.jpg')

if img is None:
  print('File could not be opened')
  sys.exit()

grayTemp = cv.cvtColor(img, cv.COLOR_BGR2GRAY)
gray = cv.equalizeHist(grayTemp)

minXY = img.shape[1] * 0.05
#print(img.shape[2])
#print("MinXY = " + str(minXY))

faces = face_cascade.detectMultiScale(
    gray,
    scaleFactor = 1.2,
    minNeighbors = 5,
    minSize = (int(minXY), int(minXY)),
  )

for(x,y,w,h) in faces:
  print("Found face")
  cv.rectangle(img, (x,y), (x+w,y+h), (0,255,0), 2)
  #id, confidence = recogniser.predict(gray[y:y+h,x:x+w])
  recogniser.predict_collect(gray[y:y+h,x:x+w], collector)
  id = collector.getMinLabel()
  confidence = collector.getMinDist()

  print(collector.getResults())
  print(str(id) + " " + str(confidence))
  # Check if confidence is less them 100 ==> "0" is perfect match 
  if (confidence < 100):
      id = names[id]
      confidence = "{0}%".format(round(100 - confidence))
  else:
      id = "unknown"
      confidence = ""
  
  #print("Found " + id + " with confidence of " + confidence)
  cv.putText(img, str(id) + " " + str(confidence), (x+5,y-5), font, 1, (255,255,255), 2)
  #cv.putText(img, str(confidence), (x+5,y+h-5), font, 3, (255,255,0), 1)

cv.imwrite(file_root + 'output.jpg', img)