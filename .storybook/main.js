module.exports = {
  stories: [
    "../html/themes/**/*.stories.mdx",
    "../html/themes/**/*.stories.@(json|yml)",
    "../html/modules/**/*.stories.mdx",
    "../html/modules/**/*.stories.@(json|yml)",
  ],
  addons: [
    '@storybook/addon-links',
    '@storybook/addon-essentials',
    '@lullabot/storybook-drupal-addon',
  ],
  framework: '@storybook/server',
  core: {
    builder: '@storybook/builder-webpack5'
  }
};
