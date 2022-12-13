# Audit Service

### What is it?
Audit is service needed to audit (log) events into database. For
now package only ships with doctrine implementation to save data,
but you can easily extend functionality (see below) to use other
means of storage.

### Requirements
PHP 8.1+  
Symfony 6.1+

### Installation
The recommended way to install is via Composer:

```shell
composer require whitedigital-eu/audit-service
```
---

### Configuration
By default after installation, audit service bundle is disabled. To enable it, just do:
yaml:
```yaml
audit:
    enable: true
```
php:
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->enabled(true);
};
```
If you have only one entity manager configured for project, 
this is all configuration needed to use the package.  
If you have multiple entity managers, you need to pass manager name
to configuration:  
```yaml
audit:
    enable: true
    entity_manager: <name>
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->enabled(true)
        ->entityManager('<name>');
};
```
If you need Audit to be available for default and secondary entity
manager, you can pass extra configuration:
```yaml
audit:
    enable: true
    entity_manager: <name>
    default_entity_manager: default
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->enabled(true)
        ->entityManager('<name>')
        ->defaultEntityManager('default');
};
```
After this, you need to update your database schema to use Audit entity.  
If using migrations:
```shell
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```
If by schema update:
```shell
bin/console doctrine:schema:update --force
```
This is it, now you can use audit. It is configured and autowired
as `AuditServiceInterface`.
```php
use WhiteDigital\Audit\AuditBundle;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;

public function __construct(private AuditServiceInterface $audit){}

$this->audit->audit(AuditBundle::EXCEPTION, 'something happened');

try {
    somefunction();
} catch (Exception $exception){
    $this->audit->auditException($exception);
}
```
---
Audit service comes with 2 event subscribers: one for exceptions and one for database events.  

**Exception subscriber**:  
By default exception subscriber audits all exceptions, except 404 response code. you can override this logic by:
```yaml
audit:
    enabled: true
    excluded_response_codes:
        - 404
        - 405
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->enabled(true)
        ->entityManager('<name>')
        ->excludedResponseCodes([
            404,
            405,
        ])
};
```
---

### Allowed Audit types
To not to create chaos within audit records, it is only allowed to use specified  audit types.  
**Default values are: `AUTHENTICATION`, `DATABASE`, `ETL_PIPELINE`, `EXCEPTION` and `EXTERNAL_CALL`.**  

If you wish to add more types, configure new types as so:
```yaml
audit:
    enabled: true
    additional_audit_types:
        - 'audit'
        - 'test'
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->enabled(true)
        ->additionalAuditTypes([
            'audit',
            'test',
        ])
};
```
---

### Overriding parts of bundle

**Overriding audit service**  
If you wish not to use audit service this package comes with, you can override it with your own.  
To do so, implement `AuditServiceInterface` into your service and configure to use your service:
```php
//config/services.php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;

$services = $containerConfigurator->services();

$services->remove(AuditServiceInterface::class);

$services
    ->set(AuditServiceInterface::class)
    ->class(YourAuditService::class);
```
**Overriding default entity**  
By default, Audit entity is based on `BaseEntity` that comes from `whitedigital-eu/entity-resource-mapper-bundle`.  
If you wish not to use this base at all, you need to create new Entity and implement `AuditEntityInterface` into it:
```php
use Doctrine\ORM\Mapping as ORM;
use WhiteDigital\Audit\Contracts\AuditEntityInterface;

#[ORM\Entity]
class AuditEntity implements AuditEntityInterface {}
```
or you can use Model from package:
```php
use Doctrine\ORM\Mapping as ORM;
use WhiteDigital\Audit\Model\Audit;

#[ORM\Entity]
class AuditEntity extends Audit {}
```
If you wish to add new properties or, maybe, use different name for Audit entity that comes from package:
```php
use Doctrine\ORM\Mapping as ORM;
use WhiteDigital\Audit\Entity\Audit as BaseAudit;

#[ORM\Entity]
#[ORM\Table('new_audit_table_name')]
class AuditEntity extends BaseAudit {}
```

Now when you use `audit()` or `auditException()` functions in your project, you need to tell service to
use your entity:
```php
use WhiteDigital\Audit\AuditBundle;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;

use App\Entity\AuditEntity; // example

public function __construct(private AuditServiceInterface $audit){}

$this->audit->audit(AuditBundle::EXCEPTION, 'something happened', [], AuditEntity::class);

$this->audit->audit(AuditBundle::EXCEPTION, 'something happened', class: AuditEntity::class);

try {
    somefunction();
} catch (Exception $exception){
    $this->audit->auditException($exception, '', AuditEntity::class);
    
    $this->audit->auditException($exception, class: AuditEntity::class);
}
```
