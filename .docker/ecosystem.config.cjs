module.exports = {
    apps: [
        {
            name: "messenger-worker",
            script: "php",
            args: "bin/console messenger:consume async -vv",
            interpreter: "/bin/sh", // Use the default shell interpreter
            instances: "max",
        },
    ],
};
