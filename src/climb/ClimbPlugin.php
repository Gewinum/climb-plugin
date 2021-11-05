<?php
namespace climb;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;

// extends PluginBase означает, что данный класс это плагин и Listener указывает на то, что там обрабатываются события.
class ClimbPlugin extends PluginBase implements Listener
{
    // Это массив, что бы люди не карабкались как не знаю кто, хд
    public $timeouts = [];

    // Данная функция вызывается при загрузке плагина.
    public function onLoad() : void
    {
    }

    // Данная функция вызывается при запуске плагина.
    public function onEnable() : void
    {
        // Зарегистрировать обработку событий в плагине.
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Вывести сообщение в консоль.
        $this->getLogger()->info("Система лазания включена!");
    }

    /**
     * Данная функция вызывается при нажатии на блок, потому что мы указали PlayerInteractEvent $event в качестве ЕДИНСТВЕННОГО аргумента.
     * Название функции может быть любым.
     */
    public function onPlayerInteract(PlayerInteractEvent $event)
    {
        // Получаем блок.
        $block = $event->getBlock();
        // Получаем игрока.
        $player = $event->getPlayer();

        // Проверяем, на земле ли человек. Если нет, то прерываем выполнение функции.
        if(!$player->isOnGround()) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        // Проверяем, барьер ли блок. Если да, то прерываем выполнение функции.
        if($block->getId() === BlockLegacyIds::BARRIER) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        /**
         * Айдишников полублоков как-то много, поэтому мы воспользуемся хитростью:
         * Проверим, исходит ли блок от абстрактного класса pocketmine\block\Slab.
         * Если да, то это полублок. Если нет, то это не полублок.
         * Slab это класс, который расширяют все классы полублоков(я честно удивлен тому, что он не абстрактный, тупизм какой-то......
         */
        if($block instanceof Slab) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        // То же самое, что и сверху, только тут проверяем ступенька ли это и класс ступеньки - pocketmine\block\Stair
        if($block instanceof Stair) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        // Получаем позицию блока(класс pocketmine\world\Position)
        $blockPosition = $block->getPosition();
        /**
         * Получаем позицию игрока(тот же класс pocketmine\world\Position, он общий)
         * и потом округляем с помощью функции floor()(возвращается pocketmine\math\Vector3, но тут это не особо важно.)
         */
        $playerVector = $player->getPosition()->floor();

        // Получить вектор игрока на уровне глаз.
        $playerEyesVector = $playerVector->add(0, 1, 0);

        /**
         * Проверяем, какая дистанция между вектором на уровне глаз игрока и позиции блока.
         * Если она больше  1, то не обрабатываем(если 1 или менее, то человек должен стоять прямо напротив блока.)
         */
        if($blockPosition->distance($playerEyesVector) > 1) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        // Сравниваем позицию блока и вектор игрока на уровне глаз.
        if($playerEyesVector->getY() !== $blockPosition->getY()) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        // Получаем вектор сверху от указанного блока.
        $blockUpperVector = $blockPosition->add(0, 1, 0);
        /**
         * Получаем блок сверху от указанного блока.
         * getPosition() получает pocketmine\world\Position
         * getWorld() получает pocketmine\world\world
         * getBlock() получает pocketmine\block\Block
         */
        $blockUpper = $block->getPosition()->getWorld()->getBlock($blockUpperVector);

        // Здесь мы проверим, блок сверху это воздух, или нет. Если нет, то не даем коду выполняться дальше.
        if($blockUpper->getId() !== BlockLegacyIds::AIR) {
            // return прерывает выполнение функции или возвращает нужное значение. К примеру, return true, return $number и тд.
            return;
        }

        /**
         * Не даем человеку карабкаться прям сразу, а через 500 миллисекунд.
         * Сначала проверяем, установлен ли таймаут для определенного игрока.
         * Если да, то проверяем, прошло ли 500 мс после последнего карабканья. Нет? Отменяем карабканье.
         */
        if(isset($this->timeouts[$player->getName()]) and $this->timeouts[$player->getName()] + 0.5 > microtime(true)) {
            return;
        }

        // Получаем силу прыжка и умножаем на 1.35, чтобы игрок прыгнул на ~2 блока.
        $y = $player->getJumpVelocity() * 1.35;

        // Создаем вектор для прыжка игрока.
        $vector = $player->getMotion()->withComponents(null, $y, null);

        // И, наконец, даем человеку прыгнуть на 2.5! :D
        $player->setMotion($vector);

        // Устанавливаем время последнего карабканья)
        $this->timeouts[$player->getName()] = microtime(true);
    }
}