## Files

```json
{ "version": "1", "type": "single", "entity": "shelf", "data": { "_ref": "s1", "name": "poetry", "title": "Poetry", "book": [ { "_ref": "b1", "title": "Odes" } ] } }
```

- `type: single` — one root in `data`; `type: collection` with an `entity` — a list of roots of that entity. A file of several entity types at once is the application's to read (`importOtherData()`).
- `entity` names the entity in snake case (`content_type`); the service locator finds the one it serves, wherever its class lives.
- `_ref` names an object in the file; another refers to it by `<relation>_ref`.

## Relations

A validation DTO declares how its relation may be given — `ReferenceHelper` tries them in turn:

| Field | Found |
|---|---|
| `shelf_id` | by id, in the database |
| `shelf_<unique>` (`shelf_name`) | by the target's unique field (`getUniqueFieldName()` of its import DTO) |
| `shelf_ref` | by its `_ref`, in the file |
| `shelf` | inline, imported with it |

`findImportDtoFromValidationDto(..., required: true)` — or `#[ImportValidationDtoHasRelation]` on the DTO — fails with an `Import*Exception` naming the relation and the fields tried.

## An entity

- The entity implements `ImportableEntityInterface`: its import service, validation DTO, import DTO.
- `ImportXValidationDto extends AbstractEntityImportValidationDto` — the file's shape, validated; nested DTOs typed by `@var ImportYValidationDto[]` (`ImportDenormalizer`).
- `ImportXDto extends AbstractEntityImportDto` — what is being imported, its relations resolved.
- `XImportExportEntityService extends AbstractImportExportEntityService` (tagged and located by entity class): `createDtoFromValidationDto()` reads the file, `createEntityFromImportDto()` builds the entity, `saveEntityFromImportDto()` saves it and its children, `connectReferences()` what can only be linked once every entity exists.
- `ExportXNormalizer extends AbstractExportNormalizer` — the export, relations written as references (`buildEntityReferencePair()`, `buildNormalizeChildCollectionPair()`); `asExisting` writes ids.

## Roots, transactions, dry runs

The application's `AbstractImportExportCollectionService` (its `getConnectReferencesOrder()`: the entity types whose references are connected, in dependency order):

```php
$report = $collection->transfer($data);              // TransferReport
$report->getSummary();                                // ['imported' => 2, 'failed' => 1]
$collection->transfer($data, dryRun: true);           // everything rolled back: what would happen
$entities = $collection->importData($data);           // stops on, and throws, the first failure
```

- **Each root in a transaction of its own**: imported whole — its children, its references connected — or not at all. A failed root leaves no row behind, and the roots after it are imported (`transfer()`) or not attempted (`importData()`).
- A failed flush closes Doctrine's entity manager: it is reset before the next root.
- The reference bags are emptied between roots and between requests (`ResetInterface`).

## Older files

A file keeps importing after the application changes: an upcaster brings it to today's shape first, so that the import services know one shape only.

```php
class ScenarioEnvironmentUpcaster implements FileUpcasterInterface   // autoconfigured
{
    public function supports(array $file): bool
    {
        return version_compare($file['version'] ?? '0', '0.4', '<');
    }

    public function upcast(array $file): array { /* the key renamed, the value read otherwise */ }
}
```

Every upcaster supporting the file is applied, in the order of their tag's priority, before `transfer()` / `importData()` read it; `upcast()` alone gives the file in today's shape. The file's `version` is what they read: an export writes the application's.
