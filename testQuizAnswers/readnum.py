def readnum(prompt):
   done = False
   while not done:
      try:
         num = int(raw_input(prompt))
         done = True
      except:
         pass
   return num
   
