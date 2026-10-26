const { checkEnvironment } = require('./environment');

module.exports = async () => {
    checkEnvironment();
};
