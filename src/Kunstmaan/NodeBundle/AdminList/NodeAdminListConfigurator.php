<?php

namespace Kunstmaan\NodeBundle\AdminList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Kunstmaan\AdminBundle\Helper\DomainConfigurationInterface;
use Kunstmaan\AdminBundle\Helper\Security\Acl\AclHelper;
use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionDefinition;
use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionMap;
use Kunstmaan\AdminListBundle\AdminList\Configurator\AbstractDoctrineORMAdminListConfigurator;
use Kunstmaan\AdminListBundle\AdminList\FilterType\ORM\BooleanFilterType;
use Kunstmaan\AdminListBundle\AdminList\FilterType\ORM\DateFilterType;
use Kunstmaan\AdminListBundle\AdminList\FilterType\ORM\StringFilterType;
use Kunstmaan\AdminListBundle\AdminList\ListAction\SimpleListAction;
use Kunstmaan\NodeBundle\Entity\Node;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * NodeAdminListConfigurator
 */
class NodeAdminListConfigurator extends AbstractDoctrineORMAdminListConfigurator
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $permission;

    /**
     * @var DomainConfigurationInterface
     */
    protected $domainConfiguration;

    /**
     * @var bool
     */
    protected $showAddHomepage;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @param EntityManager $em         The entity
     *                                  manager
     * @param AclHelper     $aclHelper  The ACL helper
     * @param string        $locale     The current
     *                                  locale
     * @param string        $permission The permission
     */
    public function __construct(EntityManager $em, AclHelper $aclHelper, $locale, $permission, AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($em, $aclHelper);
        $this->locale = $locale;
        $this->authorizationChecker = $authorizationChecker;
        $this->setPermissionDefinition(
            new PermissionDefinition(
                [$permission],
                'Kunstmaan\NodeBundle\Entity\Node',
                'n'
            )
        );
    }

    public function setDomainConfiguration(DomainConfigurationInterface $domainConfiguration)
    {
        $this->domainConfiguration = $domainConfiguration;
    }

    /**
     * @param bool $showAddHomepage
     */
    public function setShowAddHomepage($showAddHomepage)
    {
        $this->showAddHomepage = $showAddHomepage;
    }

    /**
     * Build list actions ...
     */
    public function buildListActions()
    {
        if (!$this->showAddHomepage) {
            return;
        }

        $addHomepageRoute = [
            'path' => '',
            'attributes' => [
                'class' => 'btn btn-default btn--raise-on-hover',
                'data-target' => '#add-homepage-modal',
                'data-keyboard' => 'true',
                'data-toggle' => 'modal',
                'type' => 'button',
            ],
        ];

        $this->addListAction(
            new SimpleListAction(
                $addHomepageRoute,
                'kuma_node.modal.add_homepage.h',
                null,
                '@KunstmaanNode/Admin/list_action_button.html.twig'
            )
        );
    }

    /**
     * Configure filters
     */
    public function buildFilters()
    {
        $this
            ->addFilter('title', new StringFilterType('title'), 'kuma_node.admin.list.filter.title')
            ->addFilter('created', new DateFilterType('created'), 'kuma_node.admin.list.filter.created_at')
            ->addFilter('updated', new DateFilterType('updated'), 'kuma_node.admin.list.filter.updated_at')
            ->addFilter('online', new BooleanFilterType('online'), 'kuma_node.admin.list.filter.online');
    }

    /**
     * Configure the visible columns
     */
    public function buildFields()
    {
        $this
            ->addField('title', 'kuma_node.admin.list.header.title', true, '@KunstmaanNode/Admin/title.html.twig')
            ->addField('created', 'kuma_node.admin.list.header.created_at', true)
            ->addField('updated', 'kuma_node.admin.list.header.updated_at', true)
            ->addField('online', 'kuma_node.admin.list.header.online', true, '@KunstmaanNode/Admin/online.html.twig');
    }

    /**
     * @return array
     */
    public function getEditUrlFor($item)
    {
        /* @var Node $node */
        $node = $item->getNode();

        return [
            'path' => 'KunstmaanNodeBundle_nodes_edit',
            'params' => ['id' => $node->getId()],
        ];
    }

    /**
     * @return bool
     */
    public function canAdd()
    {
        return false;
    }

    public function canEdit($item)
    {
        return $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_EDIT, $item->getNode());
    }

    /**
     * Return if current user can delete the specified item
     *
     * @param array|object $item
     *
     * @return bool
     */
    public function canDelete($item)
    {
        return false;
    }

    /**
     * @param object $item
     *
     * @return array
     */
    public function getDeleteUrlFor($item)
    {
        return [];
    }

    /**
     * @return string
     */
    public function getBundleName()
    {
        return 'KunstmaanNodeBundle';
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return 'NodeTranslation';
    }

    /**
     * Override path convention (because settings is a virtual admin subtree)
     *
     * @param string $suffix
     *
     * @return string
     */
    public function getPathByConvention($suffix = null)
    {
        if (empty($suffix)) {
            return sprintf('%s_nodes', $this->getBundleName());
        }

        return sprintf('%s_nodes_%s', $this->getBundleName(), $suffix);
    }

    /**
     * Override controller path (because actions for different entities are
     * defined in a single Settings controller).
     *
     * @return string
     */
    public function getControllerPath()
    {
        return 'KunstmaanNodeBundle:NodeAdmin';
    }

    /**
     * @param QueryBuilder $queryBuilder The query builder
     */
    public function adaptQueryBuilder(QueryBuilder $queryBuilder)
    {
        parent::adaptQueryBuilder($queryBuilder);

        $queryBuilder
            ->select('b,n')
            ->innerJoin('b.node', 'n', 'WITH', 'b.node = n.id')
            ->andWhere('b.lang = :lang')
            ->andWhere('n.deleted = :deleted')
            ->setParameter('deleted', false)
            ->addOrderBy('b.updated', 'DESC')
            ->setParameter('lang', $this->locale);

        if (!$this->domainConfiguration) {
            return;
        }

        $rootNode = $this->domainConfiguration->getRootNode();
        if (!\is_null($rootNode)) {
            $queryBuilder->andWhere('n.lft >= :left')
                ->andWhere('n.rgt <= :right')
                ->setParameter('left', $rootNode->getLeft())
                ->setParameter('right', $rootNode->getRight());
        }
    }
}
