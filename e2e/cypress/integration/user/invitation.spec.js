context("User - invitations", () => {
  before(() => {
    cy.login();
  });

  beforeEach(() => {
    cy.preserveCookie();
  });

  specify(
    "get 403 if this is not /user/USER_ID_CYPRESS/invitations",
    () =>{
      cy.request(`/user/${Cypress.env('USER_ID_CYPRESS')}/invitations`).should((response) => {
        expect(response.status).to.equal(200)
      })
      cy.request({
        url: '/user/1/invitations',
        failOnStatusCode: false
      }).should((response) => {
        expect(response.status).to.equal(403)
      })
    }
  )
});
