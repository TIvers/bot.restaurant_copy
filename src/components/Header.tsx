import { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

interface Category {
  name: string;
  image: string;
}

const Header = ({ categories }: { categories: Category[] }) => {
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [isScrolled, setIsScrolled] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  const handleSearchClick = () => {
    setSearchOpen((prev) => !prev);
  };

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const regex = /^[A-Za-zА-Яа-я0-9]*$/;
    if (regex.test(e.target.value)) {
      setSearchQuery(e.target.value);
    }
  };

  const handleScroll = () => {
    const scrollY = window.scrollY;
    setIsScrolled(scrollY > window.innerHeight); // Меняем вид после скролла на 100vh
  };

  useEffect(() => {
    if (location.pathname === '/menu') {
      window.addEventListener('scroll', handleScroll);
    }
    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, [location]);

  return (
    <header className="w-full p-[20px] pb-2 bg-[#161616] text-white">
      <div className="flex justify-between items-center h-6 mb-2">
        <div
          className="text-inter text-[14px] cursor-pointer"
          onClick={() => navigate('/menu')}
        >
          Restaurant
        </div>
        <div className="flex items-center space-x-4">
          <button onClick={handleSearchClick}>
            {/* Поисковая иконка */}
            <svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fillRule="evenodd" clipRule="evenodd" d="M0 10C0 4.477 4.477 0 10 0c5.523 0 10 4.477 10 10 0 5.523-4.477 10-10 10C4.477 20 0 15.523 0 10ZM7 9.5C7 8.119 8.119 7 9.5 7S12 8.119 12 9.5c0 1.381-1.119 2.5-2.5 2.5S7 10.881 7 9.5Zm2.5-4.5C7.015 5 5 7.015 5 9.5S7.015 14 9.5 14c.881 0 1.702-.253 2.396-.69l1.397 1.397a1 1 0 1 0 1.414-1.414l-1.397-1.397c.437-.694.69-1.515.69-2.396C14 7.015 11.985 5 9.5 5Z" fill="white"/>
            </svg>
          </button>
          <button>
            {/* Профиль */}
            <svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12.121 12.78c-.07-.01-.16-.01-.24 0-1.761-.06-3.161-1.5-3.161-3.27 0-1.81 1.46-3.28 3.28-3.28 1.81 0 3.28 1.47 3.28 3.28 0 1.77-1.39 3.21-3.16 3.27Z" stroke="white" strokeWidth="1.5"/>
              <path d="M18.74 19.38C16.96 21.01 14.6 22 12 22c-2.6 0-4.96-1.01-6.74-2.62.1-.94.7-1.86 1.77-2.58 2.74-1.82 7.22-1.82 9.94 0 1.07.72 1.67 1.64 1.77 2.58Z" stroke="white" strokeWidth="1.5"/>
              <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2C6.477 2 2 6.477 2 12s4.477 10 10 10Z" stroke="white" strokeWidth="1.5"/>
            </svg>
          </button>
        </div>
      </div>

      {searchOpen && (
        <input
          type="text"
          value={searchQuery}
          onChange={handleSearchChange}
          className="w-full bg-[#161616] text-white p-2 border-b-2 border-white focus:outline-none text-inter text-[12px]"
          placeholder="Поиск блюда..."
        />
      )}

      {/* Логика изменения отображения на странице меню */}
      <div className="flex items-center gap-4 overflow-x-auto pt-2">
  {categories.map((category, index) => (
    <button
      key={index}
      className={`text-inter text-[12px] ${
        location.pathname === '/menu'
          ? isScrolled
            ? 'text-white'
            : 'bg-[#242424] p-[14px] rounded-[12px] text-white'
          : 'text-white'
      }`}
    >
      {location.pathname === '/menu' && !isScrolled ? (
        <>
          <div className="bg-[#F3F0F0] w-[48px] h-[48px] flex items-center justify-center rounded-[12px] p-[9px] mx-auto">
            <img src={category.image} alt={category.name} className="object-contain" />
          </div>
          <span className="mt-[14px] text-white block text-center">{category.name}</span>
        </>
      ) : (
        category.name
      )}
    </button>
  ))}
</div>
    </header>
  );
};

export default Header;
