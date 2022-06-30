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
  cy.visit("/user/login");
  cy.get("#edit-name").type(username);
  cy.get("#edit-pass").type(password);
  cy.get(".user-login-form").submit();
  cy.location("pathname").should("contains", "/users/");
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
