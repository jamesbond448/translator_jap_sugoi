# translator_jap_sugoi
translator++ to translate japanese with sugoi. Offline and free.

Instruction

First step you will need to execute php you can use alternative if you want but I will give an example:

1. Install XAMPP https://www.apachefriends.org/  IMPORTANT! You will need a php version higher than 8.2 so make sure to download it. If you already have previous version, you have to unistall and install again
<details>
  <summary>Image</summary>
  
![xamp_version](https://github.com/user-attachments/assets/74beb760-2ea4-4743-b53e-e0b98dcaba41)

</details>
2. Install Sugoi Translation Toolkit at https://www.patreon.com/mingshiba/about<br />
3. Put this project code into the php folder where you installed your xampp example: D:\xampp\htdocs\<br />
4. We have to uncomment certain extension in Xampp in the apache module, click config and PHP(php.ini) there are: gd, mbstring, zip<br />
<details>
<summary>Image</summary>
  
  ![xamp_setting](https://github.com/user-attachments/assets/51fdaff1-fa6c-4cfc-bfde-6ac5376e7b9a)
  
</details>
Then go to to each line and make sure that extension=gd  does not start with ;  same with extension=mbstring  and  extension=zip
<details>
<summary>Image</summary>
  
  ![php_ini](https://github.com/user-attachments/assets/daba1f44-da4c-4455-beac-f0916402caba)
  
</details>

This program use regex (pattern match) to protect code inside text see the end for more info

The setup is done

Now to translate use translator++, create your project and save then, at the top you can export and choose excel sheet 2007, you can keep the heading on by default and you can change it in the php folder setting

Then put it in your php project folder into the input folder example just copy paste the exported folder data into it.
<details>
<summary>Image</summary>
  
  ![php_folder](https://github.com/user-attachments/assets/2a74ba0e-ea65-4cb1-853b-d5dbf4583fbf)
  
</details>

Now active XAMPP apache

<details>
  <summary>Image</summary>
  
![xamp](https://github.com/user-attachments/assets/ee5db2f8-93b4-4500-a84d-1e9a9c76482a)

</details>

Then go to http://localhost/translator++_line in your browser example of firefox
which is the project and click on Extract.php

<details>
  <summary>Image</summary>
  
![browser](https://github.com/user-attachments/assets/818d45d1-748e-497b-89f3-41dc10e38a1b)

</details>

Then with sugoi translation toolkit click on button on bottom list named sugoi file translation

<details>
  <summary>Image</summary>
  
![translate_0](https://github.com/user-attachments/assets/7518cb34-a9e6-4953-b73a-fe778e3bbf20)

</details>

Then drag the file in extract folder named extracted (number).txt into the box of file translation

<details>
  <summary>Image</summary>
  
![translate](https://github.com/user-attachments/assets/78f0cdda-326f-4d42-8f5e-2021b0b0fc8f)

</details>

Once done you now have a copy of those file in exract named extracted(number)_output.txt


Then go to http://localhost/translator++_line 
which is the project and click on Convert.php

This will give a copy of all input file but with translation in the output folder

You now have a translation for translator++, just can just import the folder then save the project and apply the translation

You change option in the setting.json file.

IMPORTANT, if you want to translate another remember to delete all files in the extract, input and output folder.

This program is slower than the mtool one beceause with translator++ there is still code in it as example 「うぅ…うん…\c[27]♥\c[0]」 to protect the text i use a regex to seperate \c[27] as an example. This make  more line as we have to divide more line for when we take it back.

You can remove the regex in the setting by false or true, you can change the regex expression or even add one of your own. <br />
example add ,"\/(XXXX)\/"   after the " at the end of the first regex, replace XXXX by your regex.
Note that on php if your regex expression has \ , you must add another like this \\ or it will give an error, you must have a delimiter like \/ at the start and the end. to add it. must start and end with parentheses () as the code wrote for it by adding each other in one line. You can test your regex online just look on google.
