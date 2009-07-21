<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\ORM\Mapping\AssociationMapping;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a unidirectional one-to-one association mapping (without inheritance).
 * Inverse side is not present.
 */
class OneToOneUnidirectionalAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $product;
    private $shipping;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->product = new ECommerceProduct();
        $this->product->setName('Doctrine 2 Manual');
        $this->shipping = new ECommerceShipping();
        $this->shipping->setDays('5');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet() {
        $this->product->setShipping($this->shipping);
        $this->_em->persist($this->product);
        $this->_em->flush();
        
        $this->assertForeignKeyIs($this->shipping->getId());
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->product->setShipping($this->shipping);
        $this->_em->persist($this->product);
        $this->product->removeShipping();

        $this->_em->flush();

        $this->assertForeignKeyIs(null);
    }

    public function _testEagerLoad()
    {
        $this->_createFixture();

        $query = $this->_em->createQuery('select p, s from Doctrine\Tests\Models\ECommerce\ECommerceProduct p left join p.shipping s');
        $result = $query->getResultList();
        $product = $result[0];
        
        $this->assertTrue($product->getShipping() instanceof ECommerceShipping);
        $this->assertEquals(1, $product->getShipping()->getDays());
    }
    
    public function testLazyLoadsObjects() {
        $this->_createFixture();
        $this->_em->getConfiguration()->setAllowPartialObjects(false);
        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $metadata->getAssociationMapping('shipping')->fetchMode = AssociationMapping::FETCH_LAZY;

        $query = $this->_em->createQuery('select p from Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $result = $query->getResultList();
        $product = $result[0];
        
        $this->assertTrue($product->getShipping() instanceof ECommerceShipping);
        $this->assertEquals(1, $product->getShipping()->getDays());
    }

    public function testDoesNotLazyLoadObjectsIfConfigurationDoesNotAllowIt() {
        $this->_createFixture();
        $this->_em->getConfiguration()->setAllowPartialObjects(true);

        $query = $this->_em->createQuery('select p from Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $result = $query->getResultList();
        $product = $result[0];
        
        $this->assertNull($product->getShipping());
    }

    protected function _createFixture()
    {
        $product = new ECommerceProduct;
        $product->setName('Php manual');
        $shipping = new ECommerceShipping;
        $shipping->setDays('1');
        $product->setShipping($shipping);
        
        $this->_em->persist($product);
        
        $this->_em->flush();
        $this->_em->clear();
    }

    public function assertForeignKeyIs($value) {
        $foreignKey = $this->_em->getConnection()->execute('SELECT shipping_id FROM ecommerce_products WHERE id=?', array($this->product->getId()))->fetchColumn();
        $this->assertEquals($value, $foreignKey);
    }
}