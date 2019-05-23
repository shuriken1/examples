#!/usr/bin/env python

import sys
import os
import numpy as np
import cv2 as cv
from PIL import Image

recogniser = cv.face.LBPHFaceRecognizer_create()
detector = cv.CascadeClassifier('/var/www/html/storage/python/face.xml')

def getImagesAndLabels(path):
    #get the path of all the files in the folder
    imagePaths=[os.path.join(path,f) for f in os.listdir(path)] 
    #create empth face list
    faceSamples=[]
    #create empty ID list
    Ids=[]
    #now looping through all the image paths and loading the Ids and the images
    for imagePath in imagePaths:
      #loading the image and converting it to gray scale
      #pilImage=Image.open(imagePath).convert('L')
      #Now we are converting the PIL image into numpy array
      #imageNp=np.array(pilImage,'uint8')
      
      img = cv.imread(imagePath)
      grayTemp = cv.cvtColor(img, cv.COLOR_BGR2GRAY)
      gray = cv.equalizeHist(grayTemp)
      
      #getting the Id from the image
      Id=int(os.path.split(imagePath)[-1].split(".")[0])
      #Id = 1
      # extract the face from the training image sample
      faces = detector.detectMultiScale(gray)
      #If a face is there then append that in the list as well as Id of it
      for (x,y,w,h) in faces:
        print("Found face in " + imagePath + ", ID: " + str(Id))
        faceSamples.append(gray[y:y+h,x:x+w])
        Ids.append(Id)
    return faceSamples,Ids

faces,Ids = getImagesAndLabels('/var/www/html/storage/files/training/faces/laura/')
recogniser.train(faces, np.array(Ids))
recogniser.save('/var/www/html/storage/files/training/trainer.yml')