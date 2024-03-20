# PHP 8.1 Compatibility Update by FireWeb
# March 2024
# Site URL: https://fireweb.ro
- Updated version of EuPlatesc WHMCS module for compatibility with PHP 8.1

Intructiuni:

1. Se va executa fisierul euplatesc.sql in baza de date
2. Se copiaza fisierele din folderul modules si ulterior 
3. Se seteaza adresele URL catre fisierele euplatesc.php si euplatesc_async.php din 
modules/gateways/callback in https://manager.euplatesc.ro/v3, in setari, in campurile 
URL callback respectiv SecStatus URL calback, 
4. Se bifeaza REPLY_SEC_STATUS si REPLY_ORIGINAL_AMOUNT

pentru whmcs v 6+ trebuie facuta urmatoarea modificare:
in fisierul de callback/euplatesc.php ,callback/euplatesc_async.php la linia 11 trebuie modificat din:

include("../../../dbconnect.php");
in
include("../../../init.php");


5. In admin WHMCS in metode de plata -> euplatesc se seteaza MID si KEY 
(se pot copia de pe prima pagina din manager apasand pe butonul in forma de pinion)
