// import { login, preserveCookie } from "../../../helper/login";



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
      cy.request(`/user/${USER_ID_CYPRESS}/invitations`).as('my_invitation')
      cy.request(`/user/1/invitations`).as('other_invitaion')

      cy.get('@my_invitation').should((response) => {
        expect(response.status).to.equal(200)
      })

      cy.get('@other_invitaion').should((response) => {
        expect(response.status).to.equal(403)
      })
    }
  )


});

