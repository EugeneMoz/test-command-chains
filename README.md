### Console Command Chaining

#### **Requirements**

Link - https://github.com/mbessolov/test-tasks/blob/master/7.md

#### How to check

1. Run **composer install**
2. Run **php bin/console bar:hi** - and get error
3. Run **php bin/console foo:hello** - and get result of both commands
4. Chain configuration in **config/services.yaml**
5. For tests run
   6. **cd bundle/EugeneMoz/ChainCommandBundle**
   7. **composer install**
   8. **vendor/bin/phpunit**
