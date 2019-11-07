# srsbsns
setup symfony 
Inside the project you will need to run composer install and  yarn install.The recaptcha has been configured to work on this domain http://srsbsns.wip where it can be tested.In config/services.yaml you'll see all the default parameters used on the site. To access the API and create an entity send a post request to http://srsbsns.wip/api/contact/create.To authenticate the API add header X-AUTH-TOKEN with value of app.admin_token parameter n config/services parameters.
For emails the system uses gmail, you activate this by adding your username and password in .env fileTo create a system admin user load fixtures. Then create a contact entity you can use the API or contact form which is the default page.
The database vaule will need to be change to suite you configuration if you decide to run it.
