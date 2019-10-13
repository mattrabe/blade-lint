module.exports = {
    '*.php': files => [
        `./vendor/bin/ecs check --fix "${files.join('" "')}"`,
        `./vendor/bin/phpcbf "${files.join('" "')}"`,
        `git add "${files.join('" "')}"`,
    ],
};
