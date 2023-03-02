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
whitedigital:
    audit:
        enable: true
        audit_entity_manager: audit
        default_entity_manager: default
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
whitedigital:
    audit:
        enabled: true
        excluded:
            response_codes:
                - 404
                - 405
```
```php
use Symfony\Config\WhitedigitalConfig;
use Symfony\Component\HttpFoundation\Response;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
whitedigital:
    audit:
        enabled: true
        excluded:
            paths:
                - '/test'
```
```php
use Symfony\Config\WhitedigitalConfig;
use Symfony\Component\HttpFoundation\Response;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
            ->excluded()
                ->paths([
                    '/test',
                ]);
};
```
Route:
```yaml
whitedigital:
    audit:
        enabled: true
        excluded:
            routes:
                - 'app_test'
```
```php
use Symfony\Config\WhitedigitalConfig;
use Symfony\Component\HttpFoundation\Response;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
whitedigital:
    audit:
        enabled: true
        additional_audit_types:
            - { www: name }
            - test
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
            ->additionalAuditTypes([
                ['test' => 'name', ],
                ['www'],
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
whitedigital:
    audit:
        enabled: true
        audit_type_interface_namespace: 'App\Audit'
        audit_type_interface_class_name: 'AuditType'
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
whitedigital:
    audit:
        enabled: true
        enable_audit_resource: true
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
whitedigital:
    audit:
        enable: true
        custom_configuration: true
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
whitedigital:
    audit:
        enable: true
        set_doctrine_mappings: false
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
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
---
**Overriding api resource options**
> **WARNING**: This overrides only configuration defined in `#ApiResource` attribute!  

If you want to override any option defined within `ApiResource` attribute on api resource defined in 
`WhiteDigital\Audit\ApiResource\Audit` you can do it with using `ExtendedApiResource` attribute.  
For example, to override `routePrefix` to get iri of `/api/audits` instead of default `/api/wd/as/audits` do:
1. Create new class that extends resource you want to override
2. Add `ExtendedApiResouce` attribute insted of `ApiResource` attribute
3. Pass only those options that you want to override, others will be taken from resource you are extending
```php
<?php declare(strict_types = 1);

namespace App\ApiResource;

use WhiteDigital\ApiResource\Attribute\ExtendedApiResource;
use WhiteDigital\Audit\ApiResource\AuditResource as WDAuditResource;

#[ExtendedApiResource(routePrefix: '')]
class AuditResource extends WDAuditResource
{
}
```
`ExtendedApiResouce` attribute checks which resource you are extending and overrides options given in extension, 
keeping other options same as in parent resource. 

> **IMPORTANT**: You need to disable bundled resource in configuration, otherwise you will have 2 instances of audit
> resource: one with `/api/audits` iri and one with `/api/wd/as/audits` iri.

```yaml
whitedigital:
    audit:
        enabled: true
        enable_audit_resource: false
```
```php
use Symfony\Config\WhitedigitalConfig;

return static function (WhitedigitalConfig $config): void {
    $config
        ->audit()
            ->enabled(true)
            ->enableAuditResource(false);
};
```
