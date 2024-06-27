<?php

declare(strict_types=1);

namespace Documentation\MappingOrmAndOdm;

use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

class MappingOrmAndOdmTest extends BaseTestCase
{
    public function testTest(): void
    {
        // Init ORM
        $config     = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__],
            isDevMode: true,
        );
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => ':memory:',
        ], $config);
        $connection->executeQuery('CREATE TABLE blog_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, body TEXT)');
        $em = new EntityManager($connection, $config);

        // Init ODM
        $dm = $this->dm;

        // Create and persist a BlogPost in both ORM and ODM
        $blogPost        = new BlogPost();
        $blogPost->title = 'Hello World!';

        $em->persist($blogPost);
        $em->flush();
        $em->clear();

        $dm->persist($blogPost);
        $dm->flush();
        $dm->clear();

        // Load the BlogPost from both ORM and ODM
        $ormBlogPost = $em->find(BlogPost::class, $blogPost->id);
        $odmBlogPost = $dm->find(BlogPost::class, $blogPost->id);

        $this->assertSame($blogPost->id, $ormBlogPost->id);
        $this->assertSame($blogPost->id, $odmBlogPost->id);
        $this->assertSame($blogPost->title, $ormBlogPost->title);
        $this->assertSame($blogPost->title, $odmBlogPost->title);

        // Different Object Managers are used, so the instances are different
        $this->assertNotSame($odmBlogPost, $ormBlogPost);

        $dm->clear();
        $em->clear();

        // Remove the BlogPost from both ORM and ODM using the repository
        $ormBlogPostRepository = $em->getRepository(BlogPost::class);
        $this->assertInstanceOf(OrmBlogPostRepository::class, $ormBlogPostRepository);
        $ormBlogPost = $ormBlogPostRepository->findPostById($blogPost->id);

        $odmBlogPostRepository = $dm->getRepository(BlogPost::class);
        $this->assertInstanceOf(OdmBlogPostRepository::class, $odmBlogPostRepository);
        $odmBlogPost = $odmBlogPostRepository->findPostById($blogPost->id);

        $this->assertSame($blogPost->title, $ormBlogPost->title);
        $this->assertSame($blogPost->title, $odmBlogPost->title);

        // Different Object Managers are used, so the instances are different
        $this->assertNotSame($odmBlogPost, $ormBlogPost);
    }
}
