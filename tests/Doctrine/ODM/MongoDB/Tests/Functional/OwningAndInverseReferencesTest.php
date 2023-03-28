<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\BlogPost;
use Documents\BrowseNode;
use Documents\Cart;
use Documents\Comment;
use Documents\Customer;
use Documents\Feature;
use Documents\FriendUser;
use Documents\Product;
use Documents\Tag;

use function assert;
use function strtotime;

class OwningAndInverseReferencesTest extends BaseTest
{
    public function testOneToOne(): void
    {
        // cart stores reference to customer
        // customer does not store reference to cart
        // customer has to load cart by querying for
        // db.cart.findOne({ 'customer.$id' : customer.id })

        // if inversedBy then isOwningSide
        // if mappedBy then isInverseSide

        $customer                 = new Customer();
        $customer->name           = 'Jon Wage';
        $customer->cart           = new Cart();
        $customer->cart->numItems = 5;
        $customer->cart->customer = $customer;
        $customer->cartTest       = 'test';
        $this->dm->persist($customer);
        $this->dm->persist($customer->cart);
        $this->dm->flush();
        $this->dm->clear();

        $customer = $this->dm->getRepository(Customer::class)->find($customer->id);
        self::assertInstanceOf(Cart::class, $customer->cart);
        self::assertEquals($customer->cart->id, $customer->cart->id);

        $check = $this->dm->getDocumentCollection($customer::class)->findOne();
        self::assertArrayHasKey('cartTest', $check);
        self::assertEquals('test', $check['cartTest']);

        $customer->cart     = null;
        $customer->cartTest = 'ok';
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($customer::class)->findOne();
        self::assertArrayHasKey('cartTest', $check);
        self::assertEquals('ok', $check['cartTest']);

        $customer = $this->dm->getRepository(Customer::class)->find($customer->id);
        self::assertInstanceOf(Cart::class, $customer->cart);
        self::assertEquals('ok', $customer->cartTest);
    }

    public function testOneToManyBiDirectional(): void
    {
        $product = new Product('Book');
        $product->addFeature(new Feature('Pages'));
        $product->addFeature(new Feature('Cover'));
        $this->dm->persist($product);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($product::class)->findOne();
        self::assertArrayNotHasKey('tags', $check);

        $check = $this->dm->getDocumentCollection(Feature::class)->findOne();
        self::assertArrayHasKey('product', $check);

        $product = $this->dm->createQueryBuilder($product::class)
            ->getQuery()
            ->getSingleResult();
        assert($product instanceof Product);
        $features = $product->features;
        self::assertCount(2, $features);
        self::assertEquals('Pages', $features[0]->name);
        self::assertEquals('Cover', $features[1]->name);
    }

    public function testOneToManySelfReferencing(): void
    {
        $node = new BrowseNode('Root');
        $node->addChild(new BrowseNode('Child 1'));
        $node->addChild(new BrowseNode('Child 2'));

        $this->dm->persist($node);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($node::class)->findOne(['parent' => ['$exists' => false]]);
        self::assertNotNull($check);
        self::assertArrayNotHasKey('children', $check);

        $root = $this->dm->createQueryBuilder($node::class)
            ->field('children')->exists(false)
            ->getQuery()
            ->getSingleResult();
        assert($root instanceof BrowseNode);
        self::assertInstanceOf(BrowseNode::class, $root);
        self::assertCount(2, $root->children);

        unset($root->children[0]);
        $this->dm->flush();

        self::assertCount(1, $root->children);

        $this->dm->refresh($root);
        self::assertCount(2, $root->children);
    }

    public function testManyToMany(): void
    {
        $baseballTag    = new Tag('baseball');
        $blogPost       = new BlogPost();
        $blogPost->name = 'Test';
        $blogPost->addTag($baseballTag);

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($blogPost::class)->findOne();
        self::assertCount(1, $check['tags']);

        $check = $this->dm->getDocumentCollection(Tag::class)->findOne();
        self::assertArrayNotHasKey('blogPosts', $check);

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();
        assert($blogPost instanceof BlogPost);
        self::assertCount(1, $blogPost->tags);

        $this->dm->clear();

        $tag = $this->dm->createQueryBuilder(Tag::class)
            ->getQuery()
            ->getSingleResult();
        self::assertInstanceOf(Tag::class, $tag);
        self::assertEquals('baseball', $tag->name);
        self::assertEquals(1, $tag->blogPosts->count());
        self::assertEquals('Test', $tag->blogPosts[0]->name);
    }

    public function testManyToManySelfReferencing(): void
    {
        $jwage  = new FriendUser('jwage');
        $fabpot = new FriendUser('fabpot');
        $fabpot->addFriend($jwage);
        $romanb = new FriendUser('romanb');
        $romanb->addFriend($jwage);
        $jwage->addFriend($fabpot);
        $jwage->addFriend($romanb);

        $this->dm->persist($jwage);
        $this->dm->persist($fabpot);
        $this->dm->persist($romanb);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->createQueryBuilder(FriendUser::class)
            ->field('name')->equals('fabpot')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();
        self::assertArrayNotHasKey('friendsWithMe', $check);

        $user = $this->dm->createQueryBuilder(FriendUser::class)
            ->field('name')->equals('fabpot')
            ->getQuery()
            ->getSingleResult();
        assert($user instanceof FriendUser);
        self::assertCount(1, $user->friendsWithMe);
        self::assertEquals('jwage', $user->friendsWithMe[0]->name);

        $this->dm->clear();

        $user = $this->dm->createQueryBuilder(FriendUser::class)
            ->field('name')->equals('romanb')
            ->getQuery()
            ->getSingleResult();
        assert($user instanceof FriendUser);
        self::assertCount(1, $user->friendsWithMe);
        self::assertEquals('jwage', $user->friendsWithMe[0]->name);

        $this->dm->clear();

        $user = $this->dm->createQueryBuilder(FriendUser::class)
            ->field('name')->equals('jwage')
            ->getQuery()
            ->getSingleResult();
        assert($user instanceof FriendUser);
        self::assertCount(2, $user->myFriends);
        self::assertEquals('fabpot', $user->myFriends[0]->name);
        self::assertEquals('romanb', $user->myFriends[1]->name);

        self::assertCount(2, $user->friendsWithMe);
        self::assertEquals('fabpot', $user->friendsWithMe[0]->name);
        self::assertEquals('romanb', $user->friendsWithMe[1]->name);

        $this->dm->clear();
    }

    public function testSortLimitAndSkipReferences(): void
    {
        $date1 = new DateTime();
        $date1->setTimestamp(strtotime('-20 seconds'));

        $date2 = new DateTime();
        $date2->setTimestamp(strtotime('-10 seconds'));

        $blogPost = new BlogPost('Test');
        $blogPost->addComment(new Comment('Comment 1', $date1));
        $blogPost->addComment(new Comment('Comment 2', $date2));

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();
        assert($blogPost instanceof BlogPost);
        self::assertEquals('Comment 1', $blogPost->comments[0]->text);
        self::assertEquals('Comment 2', $blogPost->comments[1]->text);
        self::assertEquals('Test', $blogPost->comments[0]->parent->name);
        self::assertEquals('Test', $blogPost->comments[1]->parent->name);

        $this->dm->clear();

        $comment = $this->dm->createQueryBuilder(Comment::class)
            ->getQuery()
            ->getSingleResult();
        assert($comment instanceof Comment);
        self::assertEquals('Test', $comment->parent->getName());

        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();
        assert($blogPost instanceof BlogPost);
        self::assertEquals('Comment 1', $blogPost->firstComment->getText());
        self::assertEquals('Comment 2', $blogPost->latestComment->getText());
        self::assertCount(2, $blogPost->last5Comments);

        self::assertEquals('Comment 2', $blogPost->last5Comments[0]->getText());
        self::assertEquals('Comment 1', $blogPost->last5Comments[1]->getText());

        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();

        assert($blogPost instanceof BlogPost);
        $blogPost->addComment(new Comment('Comment 3 by admin', $date1, true));
        $blogPost->addComment(new Comment('Comment 4 by admin', $date2, true));
        $this->dm->flush();
        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();
        assert($blogPost instanceof BlogPost);
        self::assertCount(2, $blogPost->adminComments);
        self::assertEquals('Comment 4 by admin', $blogPost->adminComments[0]->getText());
        self::assertEquals('Comment 3 by admin', $blogPost->adminComments[1]->getText());
    }
}
