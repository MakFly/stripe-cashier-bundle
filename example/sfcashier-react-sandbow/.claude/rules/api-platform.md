# API Platform Rules

## Conventions

### Entités
- PHP 8.4+ avec typed properties
- Attributs Doctrine (pas XML/YAML)
- Attributs API Platform pour les opérations
- Slug unique pour les produits (pas d'ID dans les URLs frontend)

### Pattern Entité
```php
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/products/{slug}', uriVariables: ['slug'])],
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    paginationItemsPerPage: 12,
)]
class Product
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;
    // ...
}
```

### Validation
- Toujours utiliser `#[Assert\*]` sur les propriétés
- Validation des prix en centimes (entier)
- Slug auto-généré si non fourni

### Sérialisation
- Groups pour contrôler l'exposition
- `product:read` pour lecture publique
- `product:write` pour écriture admin

### Filtres
```php
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'price'])]
```

### Sécurité
- `security` sur les opérations d'écriture
- `securityPostDenormalize` pour checks après hydration
- Voters pour permissions fines

### Testing
```php
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

$client->request('GET', '/api/products');
$this->assertResponseIsSuccessful();
$this->assertJsonContains(['@type' => 'hydra:Collection']);
```
