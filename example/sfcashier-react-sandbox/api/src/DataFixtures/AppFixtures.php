<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Auth\DemoUsers;
use App\Entity\Product;
use App\Entity\User;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $slugify = new Slugify();

        foreach (DemoUsers::all() as $demoUser) {
            $existingUser = $manager->getRepository(User::class)->findOneBy([
                'email' => $demoUser['email'],
            ]);

            $user = $existingUser instanceof User ? $existingUser : new User();
            $user->setEmail($demoUser['email']);
            $user->setName($demoUser['name']);
            $user->setPassword(password_hash($demoUser['password'], PASSWORD_ARGON2ID));

            if (!($existingUser instanceof User)) {
                $manager->persist($user);
            }
        }

        $products = [
            // Tech & Informatique
            [
                'name' => 'Laptop Ultrabook 15"',
                'price' => 119900,
                'description' => 'Processeur Intel Core i7 13e gen, 16 Go RAM DDR5, SSD NVMe 512 Go, écran Full HD IPS, autonomie 14h.',
                'stock' => 12,
                'seed' => 'laptop-ultrabook',
            ],
            [
                'name' => 'Casque Bluetooth ANC',
                'price' => 18900,
                'description' => 'Réduction de bruit active hybride, 30h d\'autonomie, charge rapide USB-C, son Hi-Res Audio certifié.',
                'stock' => 45,
                'seed' => 'casque-bluetooth',
            ],
            [
                'name' => 'Souris Ergonomique Sans-Fil',
                'price' => 6900,
                'description' => 'Conception ergonomique pour main droite, capteur 4000 DPI, 18 mois autonomie, récepteur USB nano.',
                'stock' => 80,
                'seed' => 'souris-ergonomique',
            ],
            [
                'name' => 'Clé USB 256 Go',
                'price' => 1990,
                'description' => 'USB 3.2 Gen1, vitesse lecture 120 Mo/s, format ultra-compact, compatible PC, Mac et consoles.',
                'stock' => 200,
                'seed' => 'cle-usb',
            ],
            [
                'name' => 'Webcam 4K Pro',
                'price' => 14900,
                'description' => 'Résolution 4K 30fps, grand angle 90°, micro dual stéréo, correction automatique de l\'éclairage.',
                'stock' => 30,
                'seed' => 'webcam-4k',
            ],

            // Maison & Cuisine
            [
                'name' => 'Cafetière à Piston 1L',
                'price' => 3490,
                'description' => 'Corps en verre borosilicate double paroi, piston en acier inoxydable, maintien en température 60 min.',
                'stock' => 55,
                'seed' => 'cafetiere-piston',
            ],
            [
                'name' => 'Blender Professionnel 2000W',
                'price' => 8900,
                'description' => 'Moteur 2000W, 6 vitesses + pulse, bol en verre trempé 1,8L, lames en acier inox, programme smoothie.',
                'stock' => 20,
                'seed' => 'blender-pro',
            ],
            [
                'name' => 'Couverture Électrique Chauffante',
                'price' => 5990,
                'description' => '180x130cm, 9 niveaux de chaleur, arrêt automatique 3h, lavable en machine, certificat CE.',
                'stock' => 38,
                'seed' => 'couverture-electrique',
            ],
            [
                'name' => 'Ensemble Couteaux Chef 5 pièces',
                'price' => 7490,
                'description' => 'Acier inoxydable X50CrMoV15, manche ergonomique antidérapant, bloc bois offert, lame forgée.',
                'stock' => 25,
                'seed' => 'couteaux-chef',
            ],
            [
                'name' => 'Aspirateur Robot Connecté',
                'price' => 29900,
                'description' => 'Mapping laser LiDAR, autonomie 150 min, réservoir 600ml, compatible Alexa & Google Home, puissance 2700 Pa.',
                'stock' => 15,
                'seed' => 'aspirateur-robot',
            ],

            // Sport & Outdoor
            [
                'name' => 'Tapis de Yoga Antidérapant',
                'price' => 3990,
                'description' => '183x61cm, épaisseur 6mm, matière TPE écologique, surface texturée double face, sangle de transport.',
                'stock' => 70,
                'seed' => 'tapis-yoga',
            ],
            [
                'name' => 'Gourde Isotherme 750ml',
                'price' => 2490,
                'description' => 'Inox 18/8 alimentaire, garde chaud 24h / froid 48h, bouchon anti-fuite, BPA free, poignée de transport.',
                'stock' => 120,
                'seed' => 'gourde-isotherme',
            ],
            [
                'name' => 'Kettlebell Fonte 16kg',
                'price' => 4990,
                'description' => 'Fonte solide avec finition antirouille, poignée texturée anti-glisse, base plate pour stabilité, norme CE.',
                'stock' => 30,
                'seed' => 'kettlebell-16kg',
            ],
            [
                'name' => 'Corde à Sauter Pro',
                'price' => 1990,
                'description' => 'Câble acier gainé PVC, roulements à billes précision, poignées ergonomiques EVA, longueur ajustable.',
                'stock' => 90,
                'seed' => 'corde-sauter',
            ],

            // Beauté & Bien-être
            [
                'name' => 'Sèche-cheveux Ionique 2400W',
                'price' => 8490,
                'description' => 'Technologie ionique anti-frisottis, 3 températures / 2 vitesses, buse concentrateur, diffuseur inclus.',
                'stock' => 35,
                'seed' => 'seche-cheveux',
            ],
            [
                'name' => 'Brosse Électrique Visage',
                'price' => 4990,
                'description' => 'Vibrations soniques 8000/min, 3 modes nettoyage, tête silicone remplaçable, étanche IPX7, autonomie 30 jours.',
                'stock' => 50,
                'seed' => 'brosse-visage',
            ],

            // Livres & Culture
            [
                'name' => 'Livre "Clean Code" - Robert C. Martin',
                'price' => 3990,
                'description' => 'Guide incontournable pour écrire du code propre, maintenable et évolutif. 464 pages, édition anglaise.',
                'stock' => 40,
                'seed' => 'livre-clean-code',
            ],
            [
                'name' => 'Carnet Moleskine A5 Ligné',
                'price' => 1790,
                'description' => 'Papier ivoire 100g, couverture rigide noire, élastique de fermeture, pochette intérieure, 240 pages.',
                'stock' => 100,
                'seed' => 'carnet-moleskine',
            ],

            // Jeux & Loisirs
            [
                'name' => 'Jeu d\'Échecs Magnétique Voyage',
                'price' => 2990,
                'description' => 'Plateau pliant 25x25cm, pièces magnétiques, rangement intégré, bois et plastique ABS, pour 2 joueurs.',
                'stock' => 42,
                'seed' => 'echecs-magnetique',
            ],
            [
                'name' => 'Pack Aquarelle 36 couleurs',
                'price' => 2290,
                'description' => 'Pigments haute qualité, tubes 12ml, teintes résistantes à la lumière, idéal débutant et intermédiaire.',
                'stock' => 65,
                'seed' => 'aquarelle-36',
            ],
        ];

        foreach ($products as $productData) {
            $slug = $slugify->slugify($productData['name']);
            $existingProduct = $manager->getRepository(Product::class)->findOneBy([
                'slug' => $slug,
            ]);

            $product = $existingProduct instanceof Product ? $existingProduct : new Product();
            $product->setName($productData['name']);
            $product->setSlug($slug);
            $product->setPrice($productData['price']);
            $product->setDescription($productData['description']);
            $product->setStock($productData['stock']);
            $product->setImageUrl('https://picsum.photos/seed/' . $productData['seed'] . '/400/300');

            if (!($existingProduct instanceof Product)) {
                $manager->persist($product);
            }
        }

        $manager->flush();
    }
}
