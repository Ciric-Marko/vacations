# vacations
vacations test


# PHP task - Vacation Module 

A module responsible for managing user vacation requests.  
 
Application works in 2 mode: as standard web application and as rest api.

Rest api can be tested using swagger-ui
http://127.0.0.1/App/Vacation/Resources/Public/swagger-ui/

DB config file is located here: /App/db_config.yml

vacation.sql contains database structure as well as single application admin user:

username: admin
password: 123456

Vacation requests are done in my details page (vacations/users/show/...).
Vacation approval is also performed on user detail page if current logged in user have rights to do so. 

