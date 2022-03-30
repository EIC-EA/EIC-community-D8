export const login = (username, password) => {
  cy.visit('/user/login')
  cy.get('#edit-name').type(username)
  cy.get('#edit-pass').type(password)
  cy.get('.user-login-form').submit()
  cy.location('pathname').should('contains', '/user/')
}

export const preserveCookie = () => {
  Cypress.Cookies.defaults({
    preserve: (cookie) => {
      return true;
    }
  })
}
