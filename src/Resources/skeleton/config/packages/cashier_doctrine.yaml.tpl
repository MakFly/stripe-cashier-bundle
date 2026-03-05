doctrine:
    orm:
        mappings:
            CashierBundle:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/vendor/makfly/stripe-cashier-bundle/src/Entity'
                prefix: 'CashierBundle\Entity'
                alias: CashierBundle
        resolve_target_entities:
            CashierBundle\Contract\BillableEntityInterface: '{{ billable_class }}'
