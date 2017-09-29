# myBB Forum 1.8 - Migrate Users and Threads Example Scripts

A collection of example scripts for migrating an existing, non myBB forum towards myBB version 1.8.

The scripts support the creation of users and threads including attachments and can be executed first in a simulation modus.

# Why
MyBB comes with their own migration tool which supports a number of existing forums.  If your forum is not supported then an alternative might be to use exported XML data files and use custom scripts for uploading those XML files into myBB.

This is exactly what I have done for migrating an existing Moodle forum with 800 users and 5000 posts towards myBB.

I have created this repository so it can be used as an example for others which might consider migrating.

# Warnings

This is NOT a myBB plugin or functionality you can use 'out-of-the-box'. 
Do NOT use the scripts directly in a production environment but try them first on a test instance.

# How To Use

These scripts are merely an example on how you could achieve a migration from data source files.  You will require development experience with PHP and myBB in order to be able to use the above.

- Export your existing forum user accounts and posts into XML files - ideally in the already existing format - If not then you either have to adjust the scripts or convert your XML format into the one I used
- The scripts should be copied into the root of the forum
- The data files can be copied into the root or any subfolder
- The attachments files can be copied in a subfolder
- If you have defined custom profile fields then you will need to adjust the user creation script as well - in my example I have added 1 new field 'Real Name'. If you don't have any custom fields then you will need to remove mine from the script.  

Additional remarks:

- You should login as administrator into the forum before executing the custom scripts.
- Always execute the scripts in 'simulation' modus first so you will get an idea of what will be happening.
- Normally you would first execute the user script and then the posts script 
- Posts which have a non-existing user ID will be created with the default user 'admin' - you might need to replace that with your default user admin account





