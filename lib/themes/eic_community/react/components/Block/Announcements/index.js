import React, {useState} from 'react'

const Announcements = ({items}) => {

  const [index, setIndex] = useState(0)

  console.log(items, index)

  function loadMore () {
    console.log('hello')
    setIndex(index + 2)
  }

  return (
    items.map((item, index) => (
      <div key={index} className={'ecl-organisation__announcements__item'}>
        <h4 className="ecl-editorial-article__aside__title">{item.title}</h4>
        <div>{item.items.slice(0, index + 2).map((annoucement, index) => <AnnoucementsItem item={annoucement} key={index} />)}</div>
        <button className="ecl-button ecl-button--ghost" onClick={loadMore}>
          <span className="ecl-button__container"><span className="ecl-button__label" data-ecl-label="true">Show 2 more</span></span>
        </button>
      </div>
    ))
  )
}

const AnnoucementsItem = ({item}) => {

  return (
    <div className="announcements ecl-u-mb-2xl">
      <h4 className="announcements__title">{item.title}</h4>
      <p>{item.description.replace(/<\/?[^>]+(>|$)/g, "")}</p>
      <a className="announcements__link" href={item.cta.link}>{item.cta.label}</a>
    </div>
  )
}

export default Announcements;
