document.addEventListener('click',function(e){
  const amount=e.target.closest('[data-amount]');
  if(amount){
    const input=document.querySelector('[name="amount"],#amount');
    if(input){input.value=amount.dataset.amount;input.focus();}
  }
});