import React, {useState} from 'react'

const Announcements = ({item}) => {

  const [load, setLoad] = useState(0)

  return (
    <div className={'ecl-organisation__announcements__item'}>
      <h4 className="ecl-editorial-article__aside__title">{item.title}</h4>
      <div>{item.items.slice(0, load + 2).map((annoucement, index) => <AnnoucementsItem item={annoucement} key={index} />)}</div>
      {
        load + 2 < item.items.length &&
        <button className="ecl-button ecl-button--ghost" onClick={() => setLoad(load + 2)}>
          <span className="ecl-button__container"><span className="ecl-button__label" data-ecl-label="true">Show 2 more</span></span>
        </button>
      }
    </div>
  )
}

const AnnoucementsItem = ({item}) => {

  return (
    <div className="announcements">
      <h4 className="announcements__title">{item.title}</h4>
      <p>{item.description.replace(/<\/?[^>]+(>|$)/g, "")}</p>
      <a className="announcements__link" href={item.cta.link}>{item.cta.label}</a>
    </div>
  )
}

export default Announcements;
