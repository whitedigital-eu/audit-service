# Audit Service

### What is it?
Audit is service needed to audit (log) events into database. For
now package only ships with doctrine implementation to save data,
but you can easily extend functionality (see below) to use other
means of storage.

### System Requirements
PHP 8.1+  
Symfony 6.2+

### Project Requirements
**2 separate Doctrine entity managers (*if using provided AuditService*)**

### Installation
The recommended way to install is via Composer:

```shell
composer require whitedigital-eu/audit-service
```
---
### Configuration
**Configuration differs between default setup and overridden one.** If you are interested in configuration 
for overriding part of package, scroll down to appropriate section.  

By default after installation, audit service bundle is disabled. To enable it, you need to add
following (or similar configuration):  
```yaml
audit:
    audit_entity_manager: audit
    default_entity_manager: default
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->auditEntityManager('audit')
        ->defaultEntityManager('default');
};
```
> `audit_entity_manager` is entity manager used for audit  
> `default_entity_manager` is entity manager you use for database operations  
> 
Logic is split between 2 managers because it is easier to audit database exception this way. If done
with one entity manager, a lot extra steps need to be taken (opening closed entity manager, checking status 
of operations, etc.)

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
This is it, now you can use audit. It is configured and autowired as `AuditServiceLocator`.
```php
use WhiteDigital\Audit\Contracts\AuditType;
use WhiteDigital\Audit\Service\AuditServiceLocator;

public function __construct(private AuditServiceLocator $audit){}

$this->audit->audit(AuditType::EXCEPTION, 'something happened');

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
    excluded:
        response_codes:
            - 404
            - 405
```
```php
use Symfony\Config\AuditConfig;
use Symfony\Component\HttpFoundation\Response;

return static function (AuditConfig $config): void {
    $config
        ->excluded()
            ->responseCodes([
                Response::HTTP_NOT_FOUND,
                Response::HTTP_METHOD_NOT_ALLOWED,
            ]);
};
```
By default exception subscriber audits all exceptions on all routes and paths. you can override this logic by:
Path:
```yaml
audit:
    excluded:
        paths:
            - '/test'
```
```php
use Symfony\Config\AuditConfig;
use Symfony\Component\HttpFoundation\Response;

return static function (AuditConfig $config): void {
    $config
        ->excluded()
            ->paths([
                '/test',
            ]);
};
```
Route:
```yaml
audit:
    excluded:
        routes:
            - 'app_test'
```
```php
use Symfony\Config\AuditConfig;
use Symfony\Component\HttpFoundation\Response;

return static function (AuditConfig $config): void {
    $config
        ->excluded()
            ->routes([
                'app_test',
            ]);
};
```
---
### Allowed Audit types
To not to create chaos within audit records, it is only allowed to use specified  audit types.  
**Default values are: `AUTHENTICATION`, `DATABASE`, `ETL_PIPELINE`, `EXCEPTION` and `EXTERNAL_CALL`.**

> If you don't have any custom types, you can use
> `WhiteDigital\Audit\Contracts\AuditType` class for default constants.

If you wish to add more types, configure new types as so:
```yaml
audit:
    additional_audit_types:
        - test1
        - test2
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->additionalAuditTypes([
            'test1',
            'test2',
        ]);
};
```
It is possible to run symfony command that generates interface based on default and added types for easier code
complete:
```shell
bin/console make:audit-types
```
This command will generate new file based on package configuration. By default, this command will make 
`App\Audit\AuditType` class. You can override this name in configuration:  
```yaml
audit:
    audit_type_interface_namespace: 'App\Audit'
    audit_type_interface_class_name: 'AuditType'
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->auditTypeInterfaceNamespace('App\Audit')
        ->auditTypeInterfaceClassName('AuditType');
};
```
> If you have this defined interface and want to add more allowed types, you can just add new types to it without
> adding types to package configuration. Allowed types will be merged from configuration and interface.

---
### Api Resource
If used within api platform, you may want to get audits as an api resource. To do that, you can enable that in 
configuration:
```yaml
audit:
    enable_audit_resource: true
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->enableAuditResource(true);
};
```
Now you should see `Audit` resource in api (and in documentation). Default iri for this resource 
is `/api/wd/as/audits`. If you want to use different iri, you can read how to do it below in override section.

---
### Overriding parts of bundle

**Overriding audit service**  
If you wish not to use audit service this package comes with, you can override it with your own.  
To do so, implement `AuditServiceInterface` into your service and configure to use your service:
```php
//config/services.php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use YourAuditService;

$services = $containerConfigurator->services();

$services
    ->set('app.audit.service')
    ->class(YourAuditService::class)
    ->tag('whitedigital.audit', ['priority' => 2]);
```
> AuditService in this package comes with priority of 1. To override it, make sure to add priority higher than that.  

If your custom AuditService does not use database as an audit storage, you need to disable part
of this package that requires 2 entity managers. You can do it like this:
```yaml
audit:
    custom_configuration: true
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->customConfiguration(true);
};
```
Using `customConfiguration` option, disables `AuditService` provided by this package. Not to brake 
application this way, dummy service is provided while you don't override it.

---
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
use WhiteDigital\Audit\Contracts\AuditType;
use WhiteDigital\Audit\Service\AuditServiceLocator;

use App\Entity\AuditEntity; // example

public function __construct(private AuditServiceLocator $audit){}

$this->audit->audit(AuditType::EXTERNAL, 'something happened', [], AuditEntity::class);

$this->audit->audit(AuditType::ETL, 'something happened', class: AuditEntity::class);

try {
    somefunction();
} catch (Exception $exception){
    $this->audit->auditException($exception, '', AuditEntity::class);
    
    $this->audit->auditException($exception, class: AuditEntity::class);
}
```
This bundle automatically adds `WhiteDigital\Audit\Entity` namespace to both given entity managers. 
If you wish to not it to do it, for example, if you don't use default entity or maybe don't store 
audits in database at all, you need to configure this to disable it:
```yaml
audit:
    set_doctrine_mappings: false
```
```php
use Symfony\Config\AuditConfig;

return static function (AuditConfig $config): void {
    $config
        ->setDoctrineMappings(false);
};
```
---
**Overriding auditing of entity events**  
You can disable entity event auditing in runtime by calling setIsEnabled setter for entity event subscriber
```php
use WhiteDigital\Audit\EventSubscriber\AuditDoctrineEventSubscriber;

public function __construct(private AuditDoctrineEventSubscriber $subscriber){}

$this->subscriber->setIsEnabled(false);
someFunction();
$this->subscriber->setIsEnabled(true);
```
