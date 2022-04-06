context('Video on demand', () => {

  specify('Assess the get cookies endpoint', () => {
    cy.request('/api/vod/cookies?file=sample').as('vodRequest')

    cy.get('@vodRequest').then(response => {
      expect(response.status).to.eq(200)
      const body = response.body
      assert.isObject(body)
      assert.isArray(body.cookies)
      assert.isString(body.stream)
    })
  })
});

