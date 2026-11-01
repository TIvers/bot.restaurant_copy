import React from "react";
import { useNavigate } from "react-router-dom";

interface Category {
  name: string;
}

interface Product {
  id: string;
  category: string;
  name: string;
  price: string;
  description: string;
  image: string;
}

interface MenuProps {
  categories: Category[];
  products: Product[];
}

const Menu: React.FC<MenuProps> = ({ categories, products }) => {
  const navigate = useNavigate();

  return (
    <div className="bg-[#161616] px-[20px] py-[10px] w-full">
      {categories.map((category, index) => (
        <div key={index} className="mb-6">
          {/* Первая строка с названием категории и кнопкой "Все >" */}
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-white text-[16px]">{category.name}</h2>
            <button
              className="bg-[#252525] text-white text-[10px] px-[10px] py-[3.5px] flex items-center rounded-[8px]"
              onClick={() =>
                navigate(`/category/${category.name.toLowerCase()}`)
              }
            >
              Все
              <svg
                width="5"
                height="10"
                viewBox="0 0 5 10"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className="ml-[6px]"
              >
                <path
                  d="M1.12383 8.23366C1.28693 8.41121 1.55137 8.41121 1.71446 8.23366L3.75563 6.00923C4.08156 5.65408 4.08143 5.07855 3.75538 4.72354L1.71296 2.49999C1.54987 2.32243 1.28542 2.32243 1.12233 2.49999C0.959225 2.67756 0.959225 2.96544 1.12233 3.14301L2.87041 5.04613C3.03354 5.22368 3.03354 5.51159 2.87041 5.68914L1.12383 7.59065C0.960732 7.7682 0.960732 8.05606 1.12383 8.23366Z"
                  fill="white"
                />
              </svg>
            </button>
          </div>

          <div className="flex gap-4 overflow-x-auto w-full flex-nowrap z-50">
  {products
    .filter((product) => product.category === category.name)
    .map((product) => (
      <div
        key={product.id}
        className="bg-[#242424] p-[10px] rounded-[16px] cursor-pointer min-w-[115px] h-[121px]"
        onClick={() => navigate(`/product-card/${product.id}`)}
      >
        <img
          src={product.image}
          alt={product.name}
          className="w-[95px] h-[75px] rounded-[8px] mx-auto"
        />
        <h3 className="text-white text-[8px] mt-[3px]">
          {product.name}
        </h3>
        <p className="text-white text-[8px] mt-[3px]">
          {product.price}
        </p>
      </div>
    ))}
</div>
        </div>
      ))}
    </div>
  );
};

export default Menu;
