#!/usr/bin/env python

import sys
import numpy as np
import cv2 as cv

file_root = '/var/www/html/storage/files/training/'
#print(sys.argv[1])
file_name = sys.argv[1] if len(sys.argv) > 1 else None

if file_name == None:
  print('No file name')
  sys.exit()

file_path = file_root + file_name + '.jpg'
#print(file_path)
img = cv.imread(file_root + file_name + '.jpg')

if img is None:
  print('File could not be opened')
  sys.exit()

gray = cv.cvtColor(img, cv.COLOR_BGR2GRAY)

face_cascade = cv.CascadeClassifier('/var/www/html/storage/python/face.xml')
#eye_cascade = cv.CascadeClassifier('eye.xml')

faces = face_cascade.detectMultiScale(gray, 1.2, 5)

count = 0
for (x,y,w,h) in faces:
  count += 1
  cv.imwrite(file_root + "faces/" + file_name + '.' + str(count) + ".jpg", img[y:y+h,x:x+w])


#cv.imwrite('/var/www/html/images/new4.jpg', img)
#print('done')
