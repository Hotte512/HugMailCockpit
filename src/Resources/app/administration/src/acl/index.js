/**
 * ACL privileges (konzept.md §7).
 *
 * Free mail dispatch is an abuse vector: every backend route additionally
 * enforces these privileges server-side via `_acl` route defaults; the
 * admin mapping here only controls UI visibility.
 *
 * Registered as additional permissions because the four roles do not map
 * onto the standard viewer/editor/creator/deleter CRUD matrix.
 */
Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'additional_permissions',
    parent: null,
    key: 'hug_mail_cockpit',
    roles: {
        viewer: {
            privileges: [
                'hug_mail_reference:read',
                'frosh_mail_archive:read',
            ],
            dependencies: [],
        },
        sender: {
            privileges: [
                'hug_mail_reference:read',
                'hug_mail_reference:create',
                'hug_mail_text_snippet:read',
                'document:read',
                'mail_template:read',
            ],
            dependencies: [],
        },
        free_sender: {
            privileges: [
                'hug_mail_reference:read',
                'hug_mail_reference:create',
                'hug_mail_text_snippet:read',
                'mail_template:read',
                'media:read',
                'media:create',
                'media_folder:read',
            ],
            dependencies: [],
        },
        twig_editor: {
            privileges: [],
            dependencies: [
                'hug_mail_cockpit.free_sender',
            ],
        },
    },
});
