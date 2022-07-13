// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
Cypress.Commands.add("login", (username = "cypress", password = "cypress") => {
  cy.logout().then(() => {
    cy.visit("/user/login");
    cy.get("#edit-name").type(username);
    cy.get("#edit-pass").type(password);
    cy.get(".user-login-form").submit();
    cy.location("pathname").should("contains", "/users/");
  })
});
Cypress.Commands.add("logout", () => {
  cy.visit("/user/logout");
});
Cypress.Commands.add("setResolution", (width = "1920", height = "1080") => {
  cy.viewport(width, height)
});
Cypress.Commands.add("entityTreeSelect", (name) => {
  cy.wait(500);
  cy.get(".entity-tree .MuiAutocomplete-input").type(name);
  cy.get(".MuiAutocomplete-popper").contains(name).click();
});
Cypress.Commands.add("fillCkeditor", (field, value) => {
  cy.window().then(window => {
    window.CKEDITOR.instances[field].setData(value)
  })
});
Cypress.Commands.add("chooseMedia", (buttonSelector) => {
  cy.get(buttonSelector).click({force: true});
  cy.wait(1000)
  cy.get('.js-media-library-views-form.ecl-form .ecl-checkbox__box').first().click({force: true})
  cy.get('.ui-dialog-buttonpane.ui-widget-content .button').click({force: true})
  cy.wait(2000)
});

Cypress.Commands.add("preserveCookie", () => {
  Cypress.Cookies.defaults({
    preserve: (cookie) => {
      return true;
    },
  });
});
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This is will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
